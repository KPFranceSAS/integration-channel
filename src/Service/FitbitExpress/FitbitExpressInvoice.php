<?php

namespace App\Service\FitbitExpress;

use App\Entity\WebOrder;
use App\Helper\Invoice\AliExpressInvoiceParent;

class FitbitExpressInvoice extends AliExpressInvoiceParent
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
