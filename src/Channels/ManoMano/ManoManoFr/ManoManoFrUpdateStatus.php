<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoUpdateStatusParent;
use App\Entity\IntegrationChannel;

class ManoManoFrUpdateStatus extends ManoManoUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
    }
}
