<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Channels\Shopify\ShopifyUpdateStatusParent;
use App\Entity\IntegrationChannel;

class FitbitCorporateUpdateStatus extends ShopifyUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }
}
