<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\MiraklUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class LeroyMerlinUpdateDelivery extends MiraklUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }
}
