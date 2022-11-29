<?php

namespace App\Channels\Shopify\OwletCare;

use App\Channels\Shopify\ShopifyPriceParent;
use App\Entity\IntegrationChannel;

class OwletCarePrice extends ShopifyPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_OWLETCARE;
    }
}
