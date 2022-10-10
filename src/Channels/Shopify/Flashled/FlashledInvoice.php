<?php

namespace App\Channels\Shopify\Flashled;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyInvoiceParent;

class FlashledInvoice extends ShopifyInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }
}
