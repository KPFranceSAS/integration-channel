<?php

namespace App\Channels\Shopify\Reencle;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class ReencleUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_REENCLE;
    }
}
