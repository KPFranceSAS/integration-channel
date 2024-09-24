<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class CorteInglesPriceStock extends MiraklPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
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
                $offer['price'] = $productMarketplace->getPriceChannel();
                $priceChannel = [];
                $priceChannel ['channel_code'] = $saleChannel->getCountryCode() == 'ES' ? 'eciStore' :  'portugalStore';
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
        return "Ligero";

    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "No voluminoso",
            "S" => "No voluminoso",
            "M" => "No voluminoso",
            "L" => "Voluminoso",
            "XL" => "Supervoluminoso",
            "XXL" => "Supervoluminoso"
        ];
    }
}
