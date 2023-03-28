<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\Channels\ManoMano\ManoManoUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class ManoManoEsUpdateDelivery extends ManoManoUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
    }
}
