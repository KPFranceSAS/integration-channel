<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpressStockParent;
use App\Entity\IntegrationChannel;

class AliExpressStock extends AliExpressStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }
}
