<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\PriceStockParent;

class PriceStockAggregator extends Aggregator
{
    public function getPriceStock(string $channel): ?PriceStockParent
    {
        return $this->getService($channel);
    }
}
