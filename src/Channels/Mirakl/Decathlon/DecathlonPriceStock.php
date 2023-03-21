<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class DecathlonPriceStock extends MiraklPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }



   


    protected function addProduct(Product $product, array $saleChannels): array
    {
        $offer = [
            "state_code" => "11",
            "update_delete" => "update",
            "shop_sku" => $product->getSku(),
            "product_id" => $product->getSku(),
            "product_id_type" => "SHOP_SKU",
            "quantity"=> $this->getStockProductWarehouse($product->getSku()),
            "logistic_class" => "M",
            "description" => 'Offer '.$product->getDescription(),
            "leadtime_to_ship" => "2",
            "all_prices" => []
        ];


      
        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
          
            if ($productMarketplace->getEnabled()) {
                $offer['price'] = $productMarketplace->getPrice();
                $priceChannel = [];
                $priceChannel ['channel_code'] = $saleChannel->getCountryCode();
                $priceChannel['unit_origin_price']= $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                if ($promotion) {
                    $priceChannel['unit_origin_price']= $promotion->getPromotionPrice() ;
                }


                $offer["all_prices"][] = $priceChannel;
            }
        }

        return $offer;
    }
}
