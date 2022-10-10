<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\ApiInterface;
use App\Service\Aggregator\Aggregator;

class ApiAggregator extends Aggregator
{
    public function getApi(string $channel): ApiInterface
    {
        return $this->getService($channel);
    }
}
