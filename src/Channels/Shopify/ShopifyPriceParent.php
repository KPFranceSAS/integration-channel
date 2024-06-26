<?php

namespace App\Channels\Shopify;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\Product;
use App\Service\Aggregator\PriceParent;

abstract class ShopifyPriceParent extends PriceParent
{
    protected $mainLocation;

    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }


    public function sendPrices(array $saleChannels)
    {
        if(count($saleChannels)>0) {
            $this->mainLocation = $this->getShopifyApi()->getMainLocation();
            $productApis = $this->getShopifyApi()->getAllProducts();
            foreach ($productApis as $productApi) {
                $this->sendPrice($productApi, $saleChannels[0]);
            }
        }
    }

    


    public function sendPrice($product, $saleChannel)
    {
        foreach ($product['variants'] as $variant) {
            $skuCode = $variant['sku'];
            $this->logger->info('Sku ' . $skuCode);
            $product = $this->getProduct($skuCode);

            if($product) {
                $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
                if ($productMarketplace->getEnabled()) {
                    $price =  $productMarketplace->getPriceChannel() ;
                    $promotion = $productMarketplace->getBestPromotionForNow();
                    $promotionPrice = $promotion ? $promotion->getPromotionPrice() : null;
                    $this->getShopifyApi()->updateVariantPrice($variant['id'], $price, $promotionPrice);
                } else {
                    $this->logger->info('Desactivate and put stock to 0');
                    $this->getShopifyApi()->setInventoryLevel($this->mainLocation['id'], $variant["inventory_item_id"], 0);
                }
            } else {
                $this->logger->info('No product in BC >>> disable');
                $this->getShopifyApi()->setInventoryLevel($this->mainLocation['id'], $variant["inventory_item_id"], 0);
            }
        }
        $this->logger->info('---------------');
    }



    public function getProduct($skuCode): ?Product
    {
        return $this->manager->getRepository(Product::class)->findOneBySku($skuCode);
    }



}
