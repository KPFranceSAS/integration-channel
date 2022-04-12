<?php

namespace App\Service\AliExpress;

use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use App\Service\Stock\StockParent;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class AliExpressStock extends StockParent
{


    public static function getBrandsFromMadrid()
    {
        return ['ECOFLOW', 'AUTELROBOTICS', 'DJI', 'PGYTECH', 'TRIDENT'];
    }

    protected $businessCentralConnector;

    protected $aliExpress;


    public function __construct(
        FilesystemOperator $awsStorage,
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpress,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($awsStorage, $manager, $logger, $mailer, $businessCentralAggregator);
        $this->aliExpress = $aliExpress;
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }

    /**
     * process all invocies directory
     *
     * @return void
     */
    public function sendStocks()
    {
        $products = $this->aliExpress->getAllActiveProducts();
        foreach ($products as $product) {
            $this->sendStock($product);
        }
    }



    public function sendStock($product)
    {
        $this->logger->info('Send stock for ' . $product->subject . ' / Id ' . $product->product_id);
        $productInfo = $this->getProductInfo($product->product_id);
        if (!$productInfo) {
            $this->logger->error('No product info');
            return;
        }
        $brand = $this->extractBrandFromResponse($productInfo);
        $stockTocHeck = $this->defineStockBrand($brand);


        $skus = $this->extractSkuFromResponse($productInfo);
        foreach ($skus as $skuCode) {
            $this->logger->info('Sku ' . $skuCode  . ' Brand ' . $brand);
            $stockBC = $this->getStockProductWarehouse($skuCode, $stockTocHeck);
            $this->logger->info('Sku ' . $skuCode  . ' / stock BC ' . $stockBC . ' units in ' . $stockTocHeck);
            $this->aliExpress->updateStockLevel($product->product_id, $skuCode, $stockBC);
        }


        $this->logger->info('---------------');
    }



    public function getProductInfo($productId)
    {
        for ($i = 0; $i < 3; $i++) {
            $productInfo = $this->aliExpress->getProductInfo($productId);
            if ($productInfo) {
                return $productInfo;
            } else {
                sleep(2);
            }
        }
        return null;
    }


    public function defineStockBrand($brand)
    {
        if ($brand && in_array($brand, AliExpressStock::getBrandsFromMadrid())) {
            return WebOrder::DEPOT_MADRID;
        }
        return WebOrder::DEPOT_CENTRAL;
    }


    protected function cleanString(string $string)
    {
        return strtoupper(trim(str_replace(' ', '', $string)));
    }


    protected function checkIfEgalString(string $string1, string $string2)
    {
        return $this->cleanString($string1) == $this->cleanString($string2);
    }

    protected function extractBrandFromResponse($productInfo)
    {
        foreach ($productInfo->aeop_ae_product_propertys->global_aeop_ae_product_property as $property) {
            if ($this->checkIfEgalString($property->attr_name, 'Brand Name')) {
                return $this->cleanString($property->attr_value);
            }
        }
        return null;
    }



    protected function extractSkuFromResponse($productInfo)
    {
        $skus = [];
        foreach ($productInfo->aeop_ae_product_s_k_us->global_aeop_ae_product_sku as $skuList) {
            if (property_exists($skuList, 'sku_code')) {
                $skus[] = $skuList->sku_code;
            }
        }
        return $skus;
    }
}
