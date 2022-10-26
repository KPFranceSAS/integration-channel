<?php

namespace App\Channels\Arise\Sonos;

use App\Channels\Arise\AriseIntegratorParent;
use App\Entity\IntegrationChannel;

class SonosIntegrator extends AriseIntegratorParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
