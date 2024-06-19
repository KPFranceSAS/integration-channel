<?php

namespace App\Channels\Mirakl\Decathlon;

use App\BusinessCentral\Connector\BusinessCentralConnector;
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
            "logistic_class" => $this->defineLogisticClass($product),
            "description" => 'Offer '.$product->getDescription(),
            "leadtime_to_ship" => "2",
            "all_prices" => [],
            "offer_additional_fields" => []
        ];

        $channelsActive = [];


      
        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
          
            if ($productMarketplace->getEnabled()) {
                $channelsActive[]=$saleChannel->getCountryCode();
                $offer['price'] = $productMarketplace->getPrice();
                $priceChannel = [];
                $priceChannel ['channel_code'] = $saleChannel->getCountryCode();
                $priceChannel['unit_origin_price']= $productMarketplace->getPrice() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
                if ($promotion) {
                    $priceChannel['unit_discount_price']= $promotion->getPromotionPrice() ;
                }


                $offer["all_prices"][] = $priceChannel;
            }
        }

        $offer["offer_additional_fields"][] = ['code'=>"active-channels", 'value' => implode(',', $channelsActive)];

        $ecotaxes =  $this->productTaxFinder->getEcoTax($product->getSku(), BusinessCentralConnector::KP_FRANCE, 'FR');


        if($ecotaxes > 0){
            $offer['offer_additional_fields'][]=[
                'code'=>"eco-contribution-amount[FR-DEEE]", 
                'value' => $ecotaxes
            ];
            $offer['offer_additional_fields'][]=[
                'code'=>"producer-id[FR-DEEE]", 
                'value' => 'FR025147_058UN1'
            ];
        }



        return $offer;
    }



    protected function getFreeLogistic() : string
    {
        return "free shipping";

    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "XS",
            "S" => "S",
            "M" => "M",
            "L" => "L",
            "XL" => "XL",
            "XXL" => "XXL"
        ];
    }
}
