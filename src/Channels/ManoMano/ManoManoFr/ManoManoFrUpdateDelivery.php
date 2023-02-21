<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class ManoManoFrUpdateDelivery extends ManoManoUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
    }
}
