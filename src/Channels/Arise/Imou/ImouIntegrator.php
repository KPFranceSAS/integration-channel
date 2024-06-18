<?php

namespace App\Channels\Arise\Imou;

use App\Channels\Arise\AriseIntegratorParent;
use App\Entity\IntegrationChannel;

class ImouIntegrator extends AriseIntegratorParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }
}
