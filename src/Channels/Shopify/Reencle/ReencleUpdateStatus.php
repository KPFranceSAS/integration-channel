<?php

namespace App\Channels\Shopify\Reencle;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\IntegrationChannel;

class ReencleUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_REENCLE;
    }
}
