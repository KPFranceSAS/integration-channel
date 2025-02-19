<?php

namespace App\Channels\Shopify\Flashled;

use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class FlashledStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
    }
}
