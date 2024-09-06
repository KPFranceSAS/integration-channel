<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\MiraklAcceptOrderParent;
use App\Entity\IntegrationChannel;

class CarrefourEsAcceptOrder extends MiraklAcceptOrderParent
{
    public function getChannel():string
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }
}
