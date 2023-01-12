<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Service\Aggregator\PriceParent;

abstract class MiraklPriceParent extends PriceParent
{
    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }

    public function sendPrices(array $products, array $saleChannels)
    {
        $this->organisePriceSaleChannel($products, $saleChannels);
        $productApis = $this->getMiraklApi()->getAllProducts();
        foreach ($productApis as $productApi) {
            $this->sendPrice($productApi);
        }
    }

    public function sendPrice($product)
    {
    }
}
