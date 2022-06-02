<?php

namespace App\Service\Aggregator;

use App\Helper\Integrator\IntegratorParent;
use App\Helper\Utils\Aggregator;

class IntegratorAggregator extends Aggregator
{
    public function getIntegrator(string $channel): IntegratorParent
    {
        return $this->getService($channel);
    }
}
