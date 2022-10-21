<?php

namespace App\Channels\Arise\Amazfit;

use App\Channels\Arise\AriseIntegratorParent;
use App\Entity\IntegrationChannel;

class AmazfitIntegrator extends AriseIntegratorParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
