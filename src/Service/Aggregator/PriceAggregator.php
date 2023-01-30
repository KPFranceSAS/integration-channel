<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\PriceParent;

class PriceAggregator extends Aggregator
{
    public function getPrice(string $channel): ?PriceParent
    {
        return $this->getService($channel);
    }
}
