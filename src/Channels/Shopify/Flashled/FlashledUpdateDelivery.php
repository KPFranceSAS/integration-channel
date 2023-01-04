<?php

namespace App\Channels\Shopify\Flashled;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class FlashledUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
    }
}
