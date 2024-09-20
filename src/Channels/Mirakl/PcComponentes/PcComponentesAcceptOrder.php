<?php

namespace App\Channels\Mirakl\PcComponentes;

use App\Channels\Mirakl\MiraklAcceptOrderParent;
use App\Entity\IntegrationChannel;

class PcComponentesAcceptOrder extends MiraklAcceptOrderParent
{
    public function getChannel():string
    {
        return IntegrationChannel::CHANNEL_PCCOMPONENTES;
    }
}
