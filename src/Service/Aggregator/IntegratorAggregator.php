<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\IntegratorParent;

class IntegratorAggregator extends Aggregator
{
    public function getIntegrator(string $channel): IntegratorParent
    {
        return $this->getService($channel);
    }
}
