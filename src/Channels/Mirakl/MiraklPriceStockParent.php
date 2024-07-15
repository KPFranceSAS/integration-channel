<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\Product;
use App\Service\Aggregator\PriceStockParent;

abstract class MiraklPriceStockParent extends PriceStockParent
{

    abstract protected function addProduct(Product $product, array $saleChannels): array;

    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }


    public function sendStocksPrices(array $products, array $saleChannels)
    {
        $offerMirakls = $this->getMiraklApi()->getOffers();
        
        

        $publishedOffers = [];
        $offers = [];
        foreach ($products as $product) {
            $publishedOffers[] = $product->getSku();
            $offers[] = $this->addProduct($product, $saleChannels);
        }


        foreach($offerMirakls as $offerMirakl) {
            if(!in_array($offerMirakl['sku'], $publishedOffers)) {
                $this->logger->info('Remove offer '.$offerMirakl['sku']);
                $offers[] = $this->getDeleteOffer($offerMirakl['sku']);
            }
        }


        if(count($offers)>0) {
            $this->getMiraklApi()->sendOfferImports($offers);
        } else {
            $this->logger->info('No offers on '.$this->getChannel());
        }

        
        return $offers;
            
    }

    abstract protected function getMappingLogisticClass(): array;


    abstract protected function getFreeLogistic(): string;



    protected function getDeleteOffer($sku)
    {
      
        return  [
            "update_delete" => "delete",
            "shop_sku" => $sku,
            "product_id" => $sku,
            "product_id_type" => "SHOP_SKU"
        ];
        
    }


    protected function defineLogisticClass(Product $product)
    {
        if($product->isFreeShipping()) {
            return $this->getFreeLogistic();
        } else {
            $mappings =$this->getMappingLogisticClass();
            if($product->getLogisticClass() && array_key_exists($product->getLogisticClass()->getCode(), $mappings)) {
                return $mappings[$product->getLogisticClass()->getCode()];
            }
        }
        return null;
    }


}
