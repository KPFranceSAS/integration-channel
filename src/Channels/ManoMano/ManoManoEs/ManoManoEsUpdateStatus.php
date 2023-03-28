<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\Channels\ManoMano\ManoManoUpdateStatusParent;
use App\Entity\IntegrationChannel;

class ManoManoEsUpdateStatus extends ManoManoUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
    }
}
