<?php

namespace App\Channels\Shopify\OwletCare;

use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;

class OwletCareStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_OWLETCARE;
    }
}
