<?php

namespace App\Channels\Shopify\Minibatt;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyInvoiceParent;

class MinibattInvoice extends ShopifyInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_MINIBATT;
    }
}
