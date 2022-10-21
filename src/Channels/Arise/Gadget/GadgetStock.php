<?php

namespace App\Channels\Arise\Gadget;

use App\Channels\Arise\AriseStockParent;
use App\Entity\IntegrationChannel;

class GadgetStock extends AriseStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
