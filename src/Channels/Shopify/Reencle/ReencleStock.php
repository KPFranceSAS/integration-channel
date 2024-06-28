<?php

namespace App\Channels\Shopify\Reencle;

use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;

class ReencleStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_REENCLE;
    }
}
