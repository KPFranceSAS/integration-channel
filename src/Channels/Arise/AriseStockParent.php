<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\StockParent;

abstract class AriseStockParent extends StockParent
{
    protected function getAriseApi(): AriseApiParent
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
        $products = $this->getAriseApi()->getAllActiveProducts();
        foreach ($products as $product) {
            $this->sendStock($product);
        }
    }



    public function sendStock($product)
    {
        $name = (property_exists($product, 'attributes') && property_exists($product->attributes, 'name')) ? $product->attributes->name : null;
        $brand = (property_exists($product, 'attributes') && property_exists($product->attributes, 'brand')) ? $product->attributes->brand : null;
        $this->logger->info('Send stock for ' . $name . ' / Id ' . $product->item_id);
        $stockTocHeck = $this->defineStockBrand($brand);
        foreach ($product->skus as $sku) {
            $this->logger->info('Sku ' . $sku->SellerSku  . ' Brand ' . $brand);
            $stockBC = $this->getStockProductWarehouse($sku->SellerSku, $stockTocHeck);
            $this->logger->info('Sku ' . $sku->SellerSku   . ' / stock BC ' . $stockBC . ' units in ' . $stockTocHeck);
            $this->getAriseApi()->updateStockLevel($product->item_id, $sku->SkuId, $sku->SellerSku, $stockBC);
        }
        $this->logger->info('---------------');
    }


    public function checkStocks(): array
    {
        $errors=[];
        $products = $this->getAriseApi()->getAllActiveProducts();
        foreach ($products as $product) {
            $name = (property_exists($product, 'attributes') && property_exists($product->attributes, 'name')) ? $product->attributes->name : null;
            $this->logger->info('Check stock for ' . $name . ' / Id ' . $product->item_id);
            foreach ($product->skus as $sku) {
                $this->logger->info('Sku ' . $sku->SellerSku);
                if (!$this->isSkuExists($sku->SellerSku)) {
                    $errors[] = 'Sku '.$sku->SellerSku. ' do not exist in BC and no sku mappings have been done also.';
                }
            }
        }
        return $errors;
    }



    public function defineStockBrand($brand)
    {
        if ($brand && in_array($brand, StockParent::getBrandsFromMadrid())) {
            return WebOrder::DEPOT_MADRID;
        }
        return WebOrder::DEPOT_LAROCA;
    }
}
