<?php

namespace App\Service\Flashled;

use App\Entity\WebOrder;
use App\Helper\Invoice\ShopifyInvoiceParent;

class FlashledInvoice extends ShopifyInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }
}
