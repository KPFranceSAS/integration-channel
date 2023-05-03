<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class LeroyMerlinPriceStock extends MiraklPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
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
            "logistic_class" => "FREE",
            "description" => $product->getDescription(),
            "leadtime_to_ship" => "2",
            "all_prices" => [],
            "offer_additional_fields" => [
                ['code'=>"vat_lmfr", 'value' => "Standard"],
                ['code'=>"vat-lmes" , 'value' => "Standard"],
                ['code'=>"vat-lmit", 'value' =>"Standard"],
                ['code'=>"shipment-origin" , 'value' => "ES"],
            ]
        ];


        
            
      
        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());

            if ($productMarketplace->getEnabled()) {

                $mirakCode= substr($saleChannel->getCode(), -3);
                $offer['price'] = $productMarketplace->getPrice();
                $priceChannel = [];
                $priceChannel ['channel_code'] = $mirakCode;
                $priceChannel['unit_origin_price']= $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                if ($promotion) {
                    $priceChannel['unit_discount_price']= $promotion->getPromotionPrice() ;
                }


                $offer["all_prices"][] = $priceChannel;
            }
        }

        return $offer;
    }
}
