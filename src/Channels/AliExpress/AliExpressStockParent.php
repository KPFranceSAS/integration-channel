<?php

namespace App\Channels\AliExpress;

use App\Channels\AliExpress\AliExpressApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\StockParent;

abstract class AliExpressStockParent extends StockParent
{
    protected function getAliExpressApi(): AliExpressApiParent
    {
        return $this->getApi();
    }



    /**
     * process all invocies directory
     *
     * @return void
     */
    public function sendStocks()
    {
        $products = $this->getAliExpressApi()->getAllActiveProducts();
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
            $this->getAliExpressApi()->updateStockLevel($product->product_id, $skuCode, $stockBC);
        }


        $this->logger->info('---------------');
    }


    /**
     *
     * @return void
     */
    public function checkStocks(): array
    {
        $errors=[];
        $products = $this->getAliExpressApi()->getAllActiveProducts();
        foreach ($products as $product) {
            $this->logger->info('Check skus for ' . $product->subject . ' / Id ' . $product->product_id);
            $productInfo = $this->getProductInfo($product->product_id);
            if ($productInfo) {
                
                $skus = $this->extractSkuFromResponse($productInfo);
                foreach ($skus as $skuCode) {
                    $this->logger->info('Sku ' . $skuCode);
                    if(!$this->isSkuExists($skuCode)){
                        $errors[] = 'Sku '.$skuCode. ' do not exist in BC and no sku mappings have been done also.';
                    }
                }
            } else {
                $this->logger->error('No product info');
            }
        }
        return $errors;
    }







    public function getProductInfo($productId)
    {
        for ($i = 0; $i < 3; $i++) {
            $productInfo = $this->getAliExpressApi()->getProductInfo($productId);
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
        if ($brand && in_array($brand, StockParent::getBrandsFromMadrid())) {
            return WebOrder::DEPOT_MADRID;
        }
        return WebOrder::DEPOT_LAROCA;
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
