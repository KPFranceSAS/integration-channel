<?php

namespace App\Service\FitbitCorporate;


use App\Entity\WebOrder;
use App\Helper\Invoice\ShopifyInvoiceParent;

class FitbitCorporateInvoice extends ShopifyInvoiceParent
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITCORPORATE;
    }
}
