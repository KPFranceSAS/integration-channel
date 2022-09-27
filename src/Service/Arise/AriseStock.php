<?php

namespace App\Service\Arise;

use App\Entity\WebOrder;
use App\Helper\Stock\AliExpressStockParent;
use App\Helper\Stock\StockParent;
use App\Service\Arise\AriseApi;

class AriseStock extends StockParent
{
   

    public function getChannel()
    {
        return WebOrder::CHANNEL_ARISE;
    }


    protected function getAriseApi(): AriseApi
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

    public function defineStockBrand($brand)
    {
        if ($brand && in_array($brand, AliExpressStockParent::getBrandsFromMadrid())) {
            return WebOrder::DEPOT_MADRID;
        }
        return WebOrder::DEPOT_LAROCA;
    }    
}
