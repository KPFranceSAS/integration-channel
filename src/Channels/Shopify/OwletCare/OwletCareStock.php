<?php

namespace App\Channels\Shopify\OwletCare;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyStockParent;

class OwletCareStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }
}
