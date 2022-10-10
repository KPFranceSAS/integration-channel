<?php

namespace App\Channels\Shopify\OwletCare;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyInvoiceParent;

class OwletCareInvoice extends ShopifyInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }
}
