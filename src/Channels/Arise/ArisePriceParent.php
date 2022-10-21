<?php

namespace App\Channels\Arise;

use App\Channels\Arise\AriseApiParent;
use App\Service\Aggregator\PriceParent;


abstract class ArisePriceParent extends PriceParent
{
    protected function getAriseApi(): AriseApiParent
    {
        return $this->getApi();
    }

    public function sendPrices(array $products, array $saleChannels)
    {
        $this->organisePriceSaleChannel($products, $saleChannels);
        $productApis = $this->getAriseApi()->getAllProducts();
        foreach ($productApis as $productApi) {
            $this->sendPrice($productApi);
        }
    }

    protected $productMarketplaces;

    protected function organisePriceSaleChannel($products, $saleChannels)
    {
        $this->productMarketplaces = [];

        foreach ($products as $product) {
            foreach ($saleChannels as $saleChannel) {
                $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
                if ($productMarketplace->getEnabled()) {
                    $this->productMarketplaces[$product->getSku()]=$productMarketplace;
                }
            }
        }
    }






    public function sendPrice($product)
    {
        $name = (property_exists($product, 'attributes') && property_exists($product->attributes, 'name')) ? $product->attributes->name : null;
        $brand = (property_exists($product, 'attributes') && property_exists($product->attributes, 'brand')) ? $product->attributes->brand : null;
        $this->logger->info('Send price for ' . $name . ' / Id ' . $product->item_id);
        foreach ($product->skus as $sku) {
            $this->logger->info('Sku ' . $sku->SellerSku  . ' Brand ' . $brand);
            if(array_key_exists($sku->SellerSku, $this->productMarketplaces)){
                $productMarketplace = $this->productMarketplaces[$sku->SellerSku];
                $price =  $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                $promotionPrice = $promotion ? $promotion->getPromotionPrice() : 0;
                $this->logger->info('Sku ' . $sku->SellerSku   . ' / price ' . $price . ' promotionPrice   ' . $promotionPrice);
                $this->getAriseApi()->updatePrice($product->item_id, $sku->SkuId, $price, $promotionPrice);
            } else {
                $this->getAriseApi()->updateStockLevel($product->item_id, $sku->SkuId,$sku->SellerSku, 0);
            }
            
        }
        $this->logger->info('---------------');
    }
}
