<?php

namespace App\Channels\Shopify\Reencle;

use App\Channels\Shopify\ShopifyPriceParent;
use App\Entity\IntegrationChannel;

class ReenclePrice extends ShopifyPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_REENCLE;
    }
}
