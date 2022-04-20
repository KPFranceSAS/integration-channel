<?php

namespace App\Helper\Stock;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressStock;
use App\Service\FitbitExpress\FitbitExpressStock;
use App\Service\OwletCare\OwletCareStock;
use Exception;

class StockAggregator
{

    private $owletCareStock;

    private $aliExpressStock;

    private $fitbitExpressStock;


    public function __construct(OwletCareStock $owletCareStock, AliExpressStock $aliExpressStock, FitbitExpressStock $fitbitExpressStock)
    {
        $this->owletCareStock = $owletCareStock;
        $this->aliExpressStock = $aliExpressStock;
        $this->fitbitExpressStock = $fitbitExpressStock;
    }


    public  function getStock(string $channel): StockParent
    {

        if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return $this->aliExpressStock;
        } else if ($channel == WebOrder::CHANNEL_OWLETCARE) {
            return $this->owletCareStock;
        } else if ($channel == WebOrder::CHANNEL_FITBITEXPRESS) {
            return $this->fitbitExpressStock;
        }

        throw new Exception("Channel $channel is not related to any stock");
    }
}
