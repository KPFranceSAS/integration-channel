<?php

namespace App\Channels\Mirakl\Boulanger;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class BoulangerPriceStock extends MiraklPriceStockParent
{

    


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
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
            "logistic_class" => "XXS",
            "description" => $product->getDescription(),
            "leadtime_to_ship" => "2",
            "all_prices" => [],
            "offer_additional_fields" => [
                [
                    'code'=>"garantie-mois",
                    'value' => "24"
                ],
            ]
        ];

        $businessCentralConnector = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);

        $itemBc = $businessCentralConnector->getItemByNumber($product->getSku());
        $addtitionalTax = $this->productTaxFinder->getEcoTaxForItem(
            $itemBc,
            BusinessCentralConnector::KP_FRANCE,
            'FR'
        );

        if($addtitionalTax >0) {
            $offer["offer_additional_fields"][] =[
                                                    'code'=>"eco-contribution-amount[FR-DEEE]",
                                                    'value' => $addtitionalTax
                                                ];
            $offer["offer_additional_fields"][] =[
                'code'=>"producer-id[FR-DEEE]",
                'value' => "FR025147_058UN1"
            ];
            $offer["offer_additional_fields"][] = [
                                                    'code'=>"ecotax-d3e",
                                                    'value' => $addtitionalTax
                                                ];
    
        }

         

            
      
        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());

            if ($productMarketplace->getEnabled()) {

                $mirakCode= "INIT";
                $offer['price'] = $productMarketplace->getPrice();
                $priceChannel = [];
                $priceChannel ['channel_code'] = $mirakCode;
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
