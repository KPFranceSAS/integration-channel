<?php

namespace App\Service\AliExpress;

use App\Entity\WebOrder;
use App\Helper\Invoice\AliExpressInvoiceParent;

class AliExpressInvoice extends AliExpressInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }
}
