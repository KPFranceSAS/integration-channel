<?php

namespace App\Service\Stock;

use App\Entity\WebOrder;
use App\Service\OwletCare\OwletCareStock;
use Exception;

class StockAggregator
{

    private $owletCareStock;


    public function __construct(OwletCareStock $owletCareStock)
    {
        $this->owletCareStock = $owletCareStock;
    }


    public  function getStock(string $channel): StockParent
    {

        if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return '';
        } else if ($channel == WebOrder::CHANNEL_OWLETCARE) {
            return $this->owletCareStock;
        }

        throw new Exception("Channel $channel is not related to any stock");
    }
}
