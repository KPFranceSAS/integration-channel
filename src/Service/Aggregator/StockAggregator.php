<?php

namespace App\Service\Aggregator;

use App\Helper\Stock\StockParent;
use App\Helper\Utils\Aggregator;

class StockAggregator extends Aggregator
{
    public  function getStock(string $channel): StockParent
    {
        return $this->getService($channel);
    }
}
