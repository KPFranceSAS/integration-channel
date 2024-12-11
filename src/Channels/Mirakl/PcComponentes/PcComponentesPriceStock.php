<?php

namespace App\Channels\Mirakl\PcComponentes;

use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class PcComponentesPriceStock extends MiraklPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_PCCOMPONENTES;
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
            "leadtime_to_ship" => "2",
            "description" => $product->getDescription(),
            "all_prices" => [],
            "offer_additional_fields" => [
                [
                    'code'=>"tipo-iva",
                    'value' => "21"
                ],
                [
                    'code'=>"canon",
                    'value' => $product->getCanonDigital()
                ],
                [
                    'code'=>"dev-gratuita",
                    'value' => true
                ],
                [
                    'code'=>"gpsr-establecido",
                    'value' => true
                ],
                [
                    'code'=>"gpsr-denominacion",
                    'value' => $product->getGsprName()
                ],
                [
                    'code'=>"gpsr-direccion-calle",
                    'value' => $product->getGsprAddress()
                ],
                [
                    'code'=>"gpsr-direccion-ciudad",
                    'value' => $product->getGsprCity()
                ],
                [
                    'code'=>"gpsr-direccion-cp",
                    'value' => $product->getGsprPostalCode()
                ],
                [
                    'code'=>"gpsr-direccion-pais",
                    'value' => $product->getGsprCountry()
                ],
                [
                    'code'=>"gpsr-direccion-email",
                    'value' => $product->getGsprEmail()
                ],
                

            ]
        ];

        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());

            if ($productMarketplace->getEnabled()) {
                $offer['price'] = $productMarketplace->getPriceChannel();

                
                $promotion = $productMarketplace->getBestPromotionForNow();
                if ($promotion) {
                    $priceChannel = [];
                    $priceChannel['unit_origin_price']= $productMarketplace->getPriceChannel() ;
                    $priceChannel['unit_discount_price']= $promotion->getPromotionPrice() ;
                    $offer["all_prices"][] = $priceChannel;

                }
                

               
            }
        }

        
        return $offer;
    }


    protected function getFreeLogistic() : string
    {
        return "gratuito";

    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "mini",
            "S" => "pequeno",
            "M" => "pequeno",
            "L" => "medio",
            "XL" => "grande",
            "XXL" => "extra"
        ];
    }



    protected function getDeleteOffer($sku)
    {
      
        $deleteOffer = parent::getDeleteOffer($sku);
        $deleteOffer["offer_additional_fields"] = [
            [
                'code'=>"tipo-iva",
                'value' => "21"
            ],
            [
                'code'=>"canon",
                'value' => 0
            ],
            [
                'code'=>"dev-gratuita",
                'value' => true
            ]
        ];
        return $deleteOffer;
        
    }



}
