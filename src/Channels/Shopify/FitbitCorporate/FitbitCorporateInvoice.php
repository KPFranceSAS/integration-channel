<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyInvoiceParent;

class FitbitCorporateInvoice extends ShopifyInvoiceParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITCORPORATE;
    }
}
