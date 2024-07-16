<?php

namespace App\Channels\Mirakl\Worten;

use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class WortenPriceStock extends MiraklPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_WORTEN;
    }



   


    protected function addProduct(Product $product, array $saleChannels): array
    {
        $offer = [
            "state_code" => "11",
            "update_delete" => "update",
            "shop_sku" => $product->getSku(),
            "product_id" => $product->getEan(),
            "product_id_type" => "EAN",
            "quantity"=> $this->getStockProductWarehouse($product->getSku()),
            "logistic_class" => $this->defineLogisticClass($product),
        
            "description" => $product->getDescription(),
            "leadtime_to_ship" => "2",
            "all_prices" => [],
            "offer_additional_fields" => [
                ['code'=>"ship-from-country-offer" , 'value' => "ES|Spain"],
            ]
        ];

      foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());

            if ($productMarketplace->getEnabled()) {
                $mirakCode ='WRT_'.$saleChannel->getCountryCode().'_ONLINE';
              
                $offer['price'] = $productMarketplace->getPriceChannel();
                $priceChannel = [];
                $priceChannel ['channel_code'] = $mirakCode;
                $priceChannel['unit_origin_price']= $productMarketplace->getPriceChannel() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
           
                if ($promotion) {
                    $priceChannel['unit_discount_price']= $promotion->getPromotionPrice() ;
                }
                $offer["all_prices"][] = $priceChannel;
            }
        }

        
        return $offer;
    }


    protected function getFreeLogistic() : string
    {
        return "freedelivery";

    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "verysmallnonheavy",
            "S" => "smallnonheavy",
            "M" => "midheavy",
            "L" => "heavy",
            "XL" => "heavy",
            "XXL" => "verylargeheavy"
        ];
    }
}
