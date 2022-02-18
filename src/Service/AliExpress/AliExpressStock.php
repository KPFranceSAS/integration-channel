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
        return WebOrder::CHANNEL_OWLETCARE;
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
        $productInfo = $this->aliExpress->getProductInfo($product->product_id);
        $skuCode = $this->extractSkuFromResponse($productInfo);
        $brand = $this->extractBrandFromResponse($productInfo);
        $stockSku = $this->extractStockFromResponse($productInfo);
        $this->logger->info('Sku ' . $skuCode  . ' BRand ' . $brand . ' / stock AE ' . $stockSku . ' units');
        $stockTocHeck = $this->defineStockBrand($brand);
        $stockBC = $this->getStockProductWarehouse($skuCode, $stockTocHeck);
        $this->logger->info('Sku ' . $skuCode  . ' / stock BC ' . $stockBC . ' units in ' . $stockTocHeck);

        if ($stockBC != $stockSku) {
            $this->aliExpress->updateStockLevel($product->product_id, $skuCode, $stockBC);
        }
        $this->logger->info('---------------');
    }


    public function defineStockBrand($brand)
    {
        if ($brand && in_array($brand, ['ECOFLOW', 'AUTELROBOTICS', 'DJI', 'PGYTECH', 'TRIDENT'])) {
            return WebOrder::DEPOT_MADRID;
        }
        return WebOrder::DEPOT_CENTRAL;
    }


    private function cleanString(string $string)
    {
        return strtoupper(trim(str_replace(' ', '', $string)));
    }


    private function checkIfEgalString(string $string1, string $string2)
    {
        return $this->cleanString($string1) == $this->cleanString($string2);
    }

    private function extractBrandFromResponse($productInfo)
    {
        foreach ($productInfo->aeop_ae_product_propertys->global_aeop_ae_product_property as $property) {
            if ($this->checkIfEgalString($property->attr_name, 'Brand Name')) {
                return $this->cleanString($property->attr_value);
            }
        }
        return null;
    }



    private function extractSkuFromResponse($productInfo)
    {
        $skuList = reset($productInfo->aeop_ae_product_s_k_us->global_aeop_ae_product_sku);
        return $skuList->sku_code;
    }


    private function extractStockFromResponse($productInfo)
    {
        $skuList = reset($productInfo->aeop_ae_product_s_k_us->global_aeop_ae_product_sku);
        return $skuList->ipm_sku_stock;
    }
}
