<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressInvoiceParent;
use App\Entity\WebOrder;

class FitbitExpressInvoice extends AliExpressInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
