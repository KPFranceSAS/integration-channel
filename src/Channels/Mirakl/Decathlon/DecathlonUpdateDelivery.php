<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class DecathlonUpdateDelivery extends MiraklUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
