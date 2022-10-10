<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\StockParent;
use App\Service\Aggregator\Aggregator;

class StockAggregator extends Aggregator
{
    public function getStock(string $channel): StockParent
    {
        return $this->getService($channel);
    }
}
