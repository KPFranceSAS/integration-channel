<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\MiraklUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class CarrefourEsUpdateDelivery extends MiraklUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }
}
