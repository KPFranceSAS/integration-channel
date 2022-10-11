<?php

namespace App\Channels\Shopify\Flashled;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\IntegrationChannel;

class FlashledUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
    }
}
