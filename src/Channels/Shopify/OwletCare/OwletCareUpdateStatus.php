<?php

namespace App\Channels\Shopify\OwletCare;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\IntegrationChannel;

class OwletCareUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_OWLETCARE;
    }
}
