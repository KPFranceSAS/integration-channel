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

    protected function getDeleteOffer($sku)
    {
        $offer = parent::getDeleteOffer($sku);
        $offer["offer_additional_fields"] = [
            [
                'code'=>"garantie-mois",
                'value' => "24"
            ],
        ];
        return $offer;
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
            "description" => $product->getDescription(),
            "leadtime_to_ship" => in_array($product->getSku(), ['ANK-PCK-7', 'ANK-PCK-8', 'ANK-PCK-9','ANK-PCK-10']) ? "10" : "2",
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
        $addtitionalTax = $itemBc ? $this->productTaxFinder->getEcoTaxForItem(
            $itemBc,
            BusinessCentralConnector::KP_FRANCE,
            'FR'
        ): 0;

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
            $offer['offer_additional_fields'][]=[
                'code'=>"ecotax-d3e", 
                'value' => $addtitionalTax
            ];
        }



        $saleChannel = $saleChannels[0];
        $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
        if ($productMarketplace->getEnabled()) {
            $offer['price'] = $productMarketplace->getPrice();
            $priceChannel = [];
            $priceChannel['unit_origin_price']= $productMarketplace->getPrice() ;
            $promotion = $productMarketplace->getBestPromotionForNow();
            if ($promotion) {
                $priceChannel['unit_discount_price']= $promotion->getPromotionPrice() ;
            }
            $offer["all_prices"][] = $priceChannel;
        }

            

        return $offer;
    }



    protected function getFreeLogistic() : string
    {
        return "XXS";

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
