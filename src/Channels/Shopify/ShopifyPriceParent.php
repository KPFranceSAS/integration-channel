<?php

namespace App\Channels\Shopify;

use App\Channels\Shopify\ShopifyApiParent;
use App\Service\Aggregator\PriceParent;

abstract class ShopifyPriceParent extends PriceParent
{
    protected $mainLocation;

    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }


    public function sendPrices(array $products, array $saleChannels)
    {
        $this->mainLocation = $this->getShopifyApi()->getMainLocation();
        $this->organisePriceSaleChannel($products, $saleChannels);
        $productApis = $this->getShopifyApi()->getAllProducts();
        foreach ($productApis as $productApi) {
            $this->sendPrice($productApi);
        }
    }

    


    public function sendPrice($product)
    {
        foreach ($product['variants'] as $variant) {
            $skuCode = $variant['sku'];
            $this->logger->info('Sku ' . $skuCode);
            if (array_key_exists($skuCode, $this->productMarketplaces)) {
                $productMarketplace = $this->productMarketplaces[$skuCode];
                $price =  $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                $promotionPrice = $promotion ? $promotion->getPromotionPrice() : null;
                $this->getShopifyApi()->updateVariantPrice($variant['id'], $price, $promotionPrice);
            } else {
                $this->logger->info('Desactivate and put stock to 0');
                $this->getShopifyApi()->setInventoryLevel($this->mainLocation['id'],$variant["inventory_item_id"],  0);
            }
        }
        $this->logger->info('---------------');
    }

}
