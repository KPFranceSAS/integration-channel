<?php

namespace App\Channels\Cdiscount;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class CdiscountUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CDISCOUNT;
    }
}
