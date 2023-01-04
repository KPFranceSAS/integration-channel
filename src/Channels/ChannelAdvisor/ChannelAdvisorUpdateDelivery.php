<?php

namespace App\Channels\ChannelAdvisor;

use App\Entity\IntegrationChannel;
use App\Service\Aggregator\UpdateDeliveryParent;

class ChannelAdvisorUpdateDelivery extends UpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CHANNELADVISOR;
    }
}
