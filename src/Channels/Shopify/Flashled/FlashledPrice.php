<?php

namespace App\Channels\Shopify\Flashled;

use App\Channels\Shopify\ShopifyPriceParent;
use App\Entity\IntegrationChannel;

class FlashledPrice extends ShopifyPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
    }
}
