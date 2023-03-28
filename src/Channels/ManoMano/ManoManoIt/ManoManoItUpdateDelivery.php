<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\Channels\ManoMano\ManoManoUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class ManoManoItUpdateDelivery extends ManoManoUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
    }
}
