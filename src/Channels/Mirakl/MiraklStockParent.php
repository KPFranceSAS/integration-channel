<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\WebOrder;
use App\Service\Aggregator\StockParent;

abstract class MiraklStockParent extends StockParent
{
    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }



    /**
     * process all invocies directory
     *
     * @return void
     */
    public function sendStocks()
    {
        $products = $this->getMiraklApi()->getAllProducts();
        foreach ($products as $product) {
            $this->sendStock($product);
        }
    }



    public function sendStock($product)
    {
    }



   



    public function checkStocks(): array
    {
        $errors=[];

        return $errors;
    }
}
