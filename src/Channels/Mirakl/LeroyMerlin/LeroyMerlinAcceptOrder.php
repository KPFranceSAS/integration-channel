<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\MiraklAcceptOrderParent;
use App\Entity\IntegrationChannel;

class LeroyMerlinAcceptOrder extends MiraklAcceptOrderParent
{
    public function getChannel():string
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }
}
