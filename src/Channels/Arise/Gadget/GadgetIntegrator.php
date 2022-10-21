<?php

namespace App\Channels\Arise\Gadget;

use App\Channels\Arise\AriseIntegratorParent;
use App\Entity\IntegrationChannel;

class GadgetIntegrator extends AriseIntegratorParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
