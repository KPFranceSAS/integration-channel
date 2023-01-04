<?php

namespace App\Channels\Arise\Sonos;

use App\Channels\Arise\AriseUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class SonosUpdateStatus extends AriseUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
