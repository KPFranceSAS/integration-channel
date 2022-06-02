<?php

namespace App\Service\Aggregator;

use App\Helper\Api\ApiInterface;
use App\Helper\Utils\Aggregator;

class ApiAggregator extends Aggregator
{
    public function getApi(string $channel): ApiInterface
    {
        return $this->getService($channel);
    }
}
