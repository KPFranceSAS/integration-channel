<?php

namespace App\Channels\Arise\Sonos;

use App\Channels\Arise\AriseUpdateStatusParent;
use App\Entity\IntegrationChannel;

class SonosUpdateStatus extends AriseUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
