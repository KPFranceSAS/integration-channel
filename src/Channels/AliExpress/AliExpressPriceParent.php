<?php

namespace App\Channels\AliExpress;

use App\Channels\AliExpress\AliExpressApiParent;
use App\Service\Aggregator\PriceParent;

abstract class AliExpressPriceParent extends PriceParent
{
    protected function getAliExpressApi(): AliExpressApiParent
    {
        return $this->getApi();
    }

    public function sendPrices(array $saleChannels)
    {
        $this->organisePriceSaleChannel($saleChannels);
        $productApis = $this->getAliExpressApi()->getAllActiveProducts();
        foreach ($productApis as $productApi) {
            $this->sendPrice($productApi);
        }
    }



    public function sendPrice($product)
    {
        $this->logger->info('Send price for ' . $product->subject . ' / Id ' . $product->product_id);
        $productInfo = $this->getProductInfo($product->product_id);
        if (!$productInfo) {
            $this->logger->error('No product info');
            return;
        }
        
        $skus = $this->extractSkuFromResponse($productInfo);
        foreach ($skus as $skuCode) {
            $this->logger->info('Sku ' . $skuCode);
            if (array_key_exists($skuCode, $this->productMarketplaces)) {
                $productMarketplace = $this->productMarketplaces[$skuCode];
                $price =  $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                $promotionPrice = $promotion ? $promotion->getPromotionPrice() : 0;
                $this->getAliExpressApi()->updatePrice($product->product_id, $skuCode, $price, $promotionPrice);
            } else {
                $this->logger->info('Descativate');
                $this->getAliExpressApi()->updateStockLevel($product->product_id, $skuCode, 0);
            }
        }


        $this->logger->info('---------------');
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


