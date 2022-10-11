<?php

namespace App\Channels\Shopify\Minibatt;

use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;

class MinibattStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MINIBATT;
    }
}
