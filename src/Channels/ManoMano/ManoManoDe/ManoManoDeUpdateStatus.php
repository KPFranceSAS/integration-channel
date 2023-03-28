<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoUpdateStatusParent;
use App\Entity\IntegrationChannel;

class ManoManoDeUpdateStatus extends ManoManoUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
    }
}
