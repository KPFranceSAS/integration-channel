<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;

class DecathlonIntegrator extends MiraklIntegratorParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
