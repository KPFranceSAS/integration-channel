<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\ProductSyncParent;

class ProductSyncAggregator extends Aggregator
{
    public function getProductSync(string $channel): ProductSyncParent
    {
        return $this->getService($channel);
    }
}
