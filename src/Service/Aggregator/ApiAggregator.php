<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\ApiInterface;

class ApiAggregator extends Aggregator
{
    public function getApi(string $channel): ?ApiInterface
    {
        return $this->getService($channel);
    }
}
