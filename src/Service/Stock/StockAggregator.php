<?php

namespace App\Service\Stock;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressStock;
use App\Service\OwletCare\OwletCareStock;
use Exception;

class StockAggregator
{

    private $owletCareStock;

    private $aliExpressStock;


    public function __construct(OwletCareStock $owletCareStock, AliExpressStock $aliExpressStock)
    {
        $this->owletCareStock = $owletCareStock;
        $this->aliExpressStock = $aliExpressStock;
    }


    public  function getStock(string $channel): StockParent
    {

        if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return $this->aliExpressStock;
        } else if ($channel == WebOrder::CHANNEL_OWLETCARE) {
            return $this->owletCareStock;
        }

        throw new Exception("Channel $channel is not related to any stock");
    }
}
