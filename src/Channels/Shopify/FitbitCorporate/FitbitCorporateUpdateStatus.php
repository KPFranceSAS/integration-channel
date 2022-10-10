<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\WebOrder;

class FitbitCorporateUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITCORPORATE;
    }
}
