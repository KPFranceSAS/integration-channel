<?php

namespace App\Channels\Shopify\MinibattPrice;

use App\Channels\Shopify\ShopifyPriceParent;
use App\Entity\IntegrationChannel;

class MinibattPrice extends ShopifyPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MINIBATT;
    }
}
