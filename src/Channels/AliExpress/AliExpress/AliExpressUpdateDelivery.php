<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class AliExpressUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }
}
