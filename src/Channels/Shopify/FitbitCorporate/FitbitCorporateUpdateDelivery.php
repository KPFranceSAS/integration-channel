<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class FitbitCorporateUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }
}
