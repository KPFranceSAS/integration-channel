<?php

namespace App\Channels\Shopify\Minibatt;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyStockParent;

class MinibattStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_MINIBATT;
    }
}
