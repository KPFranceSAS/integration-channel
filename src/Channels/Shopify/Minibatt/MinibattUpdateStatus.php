<?php

namespace App\Channels\Shopify\Minibatt;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\IntegrationChannel;

class MinibattUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MINIBATT;
    }
}
