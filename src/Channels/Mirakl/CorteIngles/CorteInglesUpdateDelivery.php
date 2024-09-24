<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\MiraklUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class CorteInglesUpdateDelivery extends MiraklUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
    }
}
