<?php

namespace App\Channels\Shopify\OwletCare;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\WebOrder;

class OwletCareUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }
}
