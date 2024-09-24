<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\MiraklAcceptOrderParent;
use App\Entity\IntegrationChannel;

class CorteInglesAcceptOrder extends MiraklAcceptOrderParent
{
    public function getChannel():string
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
    }
}
