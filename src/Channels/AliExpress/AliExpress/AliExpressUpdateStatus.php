<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpressUpdateStatusParent;
use App\Entity\IntegrationChannel;

class AliExpressUpdateStatus extends AliExpressUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }
}
