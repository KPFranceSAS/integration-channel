<?php

namespace App\Channels\Arise\Sonos;

use App\Channels\Arise\AriseStockParent;
use App\Entity\IntegrationChannel;

class SonosStock extends AriseStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
