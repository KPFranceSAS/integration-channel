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

        $offers = [];
        foreach ($products as $product) {
            $offers[] = $this->addProduct($product, $saleChannels);
        }
        if(count($offers)>0) {
            $this->getMiraklApi()->sendOfferImports($offers);
        } else {
            $this->logger->info('No offers on '.$this->getChannel());
        }
            
    }


}
