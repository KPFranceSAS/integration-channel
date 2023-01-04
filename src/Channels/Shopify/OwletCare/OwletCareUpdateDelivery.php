<?php

namespace App\Channels\Shopify\OwletCare;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class OwletCareUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_OWLETCARE;
    }
}
