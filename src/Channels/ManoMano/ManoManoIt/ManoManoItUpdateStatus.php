<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\Channels\ManoMano\ManoManoUpdateStatusParent;
use App\Entity\IntegrationChannel;

class ManoManoItUpdateStatus extends ManoManoUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
    }
}
