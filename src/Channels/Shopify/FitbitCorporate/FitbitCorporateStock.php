<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyStockParent;

class FitbitCorporateStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITCORPORATE;
    }
}
