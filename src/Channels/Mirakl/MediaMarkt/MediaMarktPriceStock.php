<?php

namespace App\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class MediaMarktPriceStock extends MiraklPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }



    protected function getDeleteOffer($sku)
    {
        $offer = parent::getDeleteOffer($sku);
        $offer["offer_additional_fields"] = [
            ['code'=>"strike-price-type" , 'value' => "lowest-prior-price-according-to-state-law"],
        ];
        return $offer;
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
                ['code'=>"strike-price-type" , 'value' => "lowest-prior-price-according-to-state-law"],
            ]
        ];

        foreach ($saleChannels as $saleChannel) {
            $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());

            if ($productMarketplace->getEnabled()) {
                $offer['price'] = $productMarketplace->getPriceChannel();
                $promotion = $productMarketplace->getBestPromotionForNow();
                if($promotion) {
                    $offer['discount'] = [
                        'price' =>  $promotion->getPromotionPrice(),
                    ] ;
                }
                
            }
        }


        return $offer;
    }



    


    protected function getFreeLogistic() : string
    {
        return "FS";

    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "LST",
            "S" => "LBNT",
            "M" => "LBT",
            "L" => "PST",
            "XL" => "PMT",
            "XXL" => "PBT"
        ];
    }



            

}
