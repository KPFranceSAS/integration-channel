<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressUpdateStatusParent;
use App\Entity\IntegrationChannel;

class FitbitExpressUpdateStatus extends AliExpressUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITEXPRESS;
    }
}
