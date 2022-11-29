<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Channels\Shopify\ShopifyPriceParent;
use App\Entity\IntegrationChannel;

class FitbitCorporatePrice extends ShopifyPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }
}
