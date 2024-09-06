<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\MiraklPriceStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\Product;

class CarrefourEsPriceStock extends MiraklPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
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
            "offer_additional_fields" => []
        ];

        $saleChannel = $saleChannels[0];
        $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
        if ($productMarketplace->getEnabled()) {
            $offer['price'] = $productMarketplace->getPriceChannel();
            $priceChannel = [];
            $priceChannel['unit_origin_price']= $productMarketplace->getPriceChannel() ;
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
        return "M";

    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "A",
            "S" => "B",
            "M" => "C",
            "L" => "D",
            "XL" => "D",
            "XXL" => "E"
        ];
    }
}
