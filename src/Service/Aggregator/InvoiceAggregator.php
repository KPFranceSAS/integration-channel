<?php

namespace App\Service\Aggregator;

use App\Service\Aggregator\InvoiceParent;
use App\Service\Aggregator\Aggregator;

class InvoiceAggregator extends Aggregator
{
    public function getInvoice(string $channel): InvoiceParent
    {
        return $this->getService($channel);
    }
}
