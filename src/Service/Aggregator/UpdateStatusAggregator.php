<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\Aggregator;
use App\Service\Aggregator\UpdateStatusParent;

class UpdateStatusAggregator extends Aggregator
{
    public function getInvoice(string $channel): UpdateStatusParent
    {
        return $this->getService($channel);
    }
}
