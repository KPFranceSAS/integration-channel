<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\BusinessCentral\Connector\BusinessCentralConnector;
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
            "product_id" => $product->getEan(),
            "product_id_type" => "EAN",
            "quantity"=> $this->getStockProductWarehouse($product->getSku()),
            "logistic_class" => $this->defineLogisticClass($product),
        
            "description" => $product->getDescription(),
            "leadtime_to_ship" => in_array($product->getSku(), ['ANK-PCK-7', 'ANK-PCK-8', 'ANK-PCK-9','ANK-PCK-10']) ? "10" : "2",
            "all_prices" => [],
            "offer_additional_fields" => [
                ['code'=>"vat-lmfr", 'value' => "Standard"],
                ['code'=>"vat-lmes" , 'value' => "Standard"],
                ['code'=>"vat-lmit", 'value' =>"Standard"],
                ['code'=>"shipment-origin" , 'value' => "ES"],

            ]
        ];


        if($product->getEcotax() > 0) {
            $offer['offer_additional_fields'][]=[
                'code'=>"eco-contribution-amount[FR-DEEE]",
                'value' => $product->getEcotax()
            ];
            $offer['offer_additional_fields'][]=[
                'code'=>"producer-id[FR-DEEE]",
                'value' => 'FR025147_058UN1'
            ];

            $offer['offer_additional_fields'][]=[
                'code'=>"ecopart-amount",
                'value' => $product->getEcotax()
            ];
        }
        




        $channelsActive = [];

        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());

            if ($productMarketplace->getEnabled()) {
                $codeChannel ='LM'.$saleChannel->getCountryCode();
                $channelsActive[]=$codeChannel ;
                $mirakCode= substr((string) $saleChannel->getCode(), -3);
                $offer['price'] = $productMarketplace->getPriceChannel();
                $priceChannel = [];
                $priceChannel ['channel_code'] = $mirakCode;
                $priceChannel['unit_origin_price']= $productMarketplace->getPriceChannel() ;
                $promotion = $productMarketplace->getBestPromotionForNow();
           
                if ($promotion) {
                    $priceChannel['unit_discount_price']= $promotion->getPromotionPrice() ;
                    $finalDiscount =  round((($priceChannel['unit_origin_price'] - $priceChannel['unit_discount_price'])/ $priceChannel['unit_origin_price'])*100, 2);
                    $offer['offer_additional_fields'][]=[
                        'code'=>"discount-percentage-".strtolower($codeChannel), 'value' => $finalDiscount
                    ];

                } else {
                    $offer['offer_additional_fields'][]=[
                        'code'=>"discount-percentage-".strtolower($codeChannel), 'value' => ""
                    ];
                }


                $offer["all_prices"][] = $priceChannel;
            }
        }

        $offer["offer_additional_fields"][] = ['code'=>"exclusive-channels", 'value' => implode(',', $channelsActive)];

        return $offer;
    }


    protected function getFreeLogistic() : string
    {
        return "FREE";

    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "XXXS",
            "S" => "XXXS",
            "M" => "XXXS",
            "L" => "XXS",
            "XL" => "XS",
            "XXL" => "INIT"
        ];
    }
}
