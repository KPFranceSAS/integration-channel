<?php

namespace App\Channels\Mirakl;


use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\Product;
use App\Service\Aggregator\PriceStockParent;
use Mirakl\MMP\Shop\Request\Offer\UpdateOffersRequest;


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
            $this->getMiraklApi()->sendOfferImports($offers);
            
    }


}
