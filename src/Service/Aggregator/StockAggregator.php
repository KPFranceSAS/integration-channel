<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\StockParent;

class StockAggregator extends Aggregator
{
    public function getStock(string $channel): ?StockParent
    {
        return $this->getService($channel);
    }
}
