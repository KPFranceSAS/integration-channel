<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Entity\WebOrder;
use App\Channels\AliExpress\AliExpressInvoiceParent;

class AliExpressInvoice extends AliExpressInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }
}
