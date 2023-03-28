<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class ManoManoDeUpdateDelivery extends ManoManoUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
    }
}
