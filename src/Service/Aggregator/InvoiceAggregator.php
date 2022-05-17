<?php

namespace App\Service\Aggregator;

use App\Helper\Invoice\InvoiceParent;
use App\Helper\Utils\Aggregator;

class InvoiceAggregator extends Aggregator
{

    public  function getInvoice(string $channel): InvoiceParent
    {
        return $this->getService($channel);
    }
}
