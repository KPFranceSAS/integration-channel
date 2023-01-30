<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\UpdateDeliveryParent;

class UpdateDeliveryAggregator extends Aggregator
{
    public function getDelivery(string $channel): ?UpdateDeliveryParent
    {
        return $this->getService($channel);
    }
}
