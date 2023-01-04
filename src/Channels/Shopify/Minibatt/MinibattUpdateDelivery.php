<?php

namespace App\Channels\Shopify\Minibatt;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class MinibattUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MINIBATT;
    }
}
