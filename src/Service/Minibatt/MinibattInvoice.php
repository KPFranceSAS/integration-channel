<?php

namespace App\Service\Minibatt;


use App\Entity\WebOrder;
use App\Helper\Invoice\ShopifyInvoiceParent;

class MinibattInvoice extends ShopifyInvoiceParent
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_MINIBATT;
    }
}
