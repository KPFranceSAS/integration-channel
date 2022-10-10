<?php

namespace App\Channels\Shopify\Minibatt;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\WebOrder;

class MinibattUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_MINIBATT;
    }
}
