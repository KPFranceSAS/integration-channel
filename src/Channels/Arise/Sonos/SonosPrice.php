<?php

namespace App\Channels\Arise\Sonos;

use App\Channels\Arise\ArisePriceParent;
use App\Entity\IntegrationChannel;

class SonosPrice extends ArisePriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
