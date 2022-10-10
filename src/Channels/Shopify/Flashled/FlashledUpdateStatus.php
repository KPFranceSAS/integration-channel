<?php

namespace App\Channels\Shopify\Flashled;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\WebOrder;

class FlashledUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }
}
