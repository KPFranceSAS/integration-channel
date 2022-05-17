<?php

namespace App\Service\OwletCare;


use App\Entity\WebOrder;
use App\Helper\Invoice\ShopifyInvoiceParent;

class OwletCareInvoice extends ShopifyInvoiceParent
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }
}
