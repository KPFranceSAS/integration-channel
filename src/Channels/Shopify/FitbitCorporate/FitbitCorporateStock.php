<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Channels\Shopify\ShopifyStockParent;
use App\Entity\IntegrationChannel;

class FitbitCorporateStock extends ShopifyStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }
}
