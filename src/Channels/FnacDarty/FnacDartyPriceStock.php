<?php

namespace App\Channels\FnacDarty;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\FnacDarty\FnacDartyApi;
use App\Entity\Product;
use App\Service\Aggregator\PriceStockParent;
use DateTimeZone;

abstract class FnacDartyPriceStock extends PriceStockParent
{


    abstract public function getChannel(): string;
   
    protected function getFnacDartyApi(): FnacDartyApi
    {
        return $this->getApi();
    }


    public function sendStocksPrices(array $products, array $saleChannels)
    {

        $offers = [];
        foreach ($products as $product) {
            $offers[] = $this->addProduct($product, $saleChannels);
        }
        if(count($offers)>0) {
            $this->getFnacDartyApi()->sendOffers($offers);
            
        } else {
            $this->logger->info('No offers on '.$this->getChannel());
        }
            
    }



    protected function addProduct(Product $product, array $saleChannels): array
    {
        $offer = [
            "sku" => $product->getSku(),
            "ean" => $product->getEan(),
            "quantity"=> $this->getStockProductWarehouse($product->getSku()),
            "description" => $product->getDescription(),
            "is_shipping_free" => $product->isFreeShipping() ? '1' : '0',
            
        ];

        $logisticId = $this->defineLogisticClass($product);
        if($logisticId){
            $offer['logisticId']=$logisticId; 
        }

        $saleChannel = $saleChannels[0];
        $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
        $offer['price'] = $productMarketplace->getPrice();
        $promotion = $productMarketplace->getBestPromotionForNow();
        if ($promotion) {
            if($promotion->isFixedType()) {
                $valuePromotion = $productMarketplace->getPrice() - $promotion->getFixedAmount();
                $typePromotion = 'fixed';
            } else {
                $valuePromotion = $promotion->getPercentageAmount();
                $typePromotion = 'percentage';
            }
            
            /** @var $beginDate DateTime */
            $beginDate = $promotion->getBeginDate();
            $beginDate->setTimezone(new DateTimeZone('+02:00'));

            /** @var $endDate DateTime */
            $endDate = $promotion->getEndDate();
            $endDate->setTimezone(new DateTimeZone('+02:00'));

            $offer['promotion']= [
                'discount_value' => $valuePromotion,
                'discount_type' => $typePromotion,
                'type' => 'GoodDeal',
                'starts_at' => $beginDate->format('Y-m-d\TH:i:sP'),
                'ends_at' => $endDate->format('Y-m-d\TH:i:sP')
            ] ;
        }
        






        $businessCentralConnector = $this->businessCentralAggregator->getBusinessCentralConnector(BusinessCentralConnector::KP_FRANCE);

        $itemBc = $businessCentralConnector->getItemByNumber($product->getSku());
        $addtitionalTax = $this->productTaxFinder->getEcoTaxForItem(
            $itemBc,
            BusinessCentralConnector::KP_FRANCE,
            'FR'
        );

        if($addtitionalTax >0) {
            $offer['deee_tax'] =$addtitionalTax;
        } else {
            $offer['deee_tax'] = 0;
        }

            

        return $offer;
    }


    protected function defineLogisticClass(Product $product){
        $mappings =$this->getMappingLogisticClass();
        if($product->getLogisticClass() && array_key_exists($product->getLogisticClass()->getCode(), $mappings)){
                return $mappings[$product->getLogisticClass()->getCode()];
        }
        return null;
    }


    public function getMappingLogisticClass(): array
    {
        return [
            "XS" => "201",
            "S" => "202",
            "M" => "203",
            "L" => "204",
            "XL" => "205",
            "XXL" => "205"
        ];
    }


}
