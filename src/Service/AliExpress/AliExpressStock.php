<?php

namespace App\Service\AliExpress;

use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use App\Service\Stock\StockParent;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class AliExpressStock extends StockParent

{

    protected $businessCentralConnector;

    protected $aliExpress;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpress,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator);
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
        $stockSku = $this->extractStockFromResponse($productInfo);
        $this->logger->info('Sku ' . $skuCode  . ' / stock AE ' . $stockSku . ' units');
        $stockBC = $this->getStockProductWarehouse($skuCode);
        $this->logger->info('Sku ' . $skuCode  . ' / stock BC ' . $stockBC . ' units');

        if ($stockBC == $stockSku) {
            return;
        } else {
            //$this->aliExpress->updateStockLevel($product->product_id, $skuCode, $stockBC);
        }
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
