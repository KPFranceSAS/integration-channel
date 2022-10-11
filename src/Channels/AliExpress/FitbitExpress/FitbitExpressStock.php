<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressStockParent;
use App\Entity\IntegrationChannel;
class FitbitExpressStock extends AliExpressStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITEXPRESS;
    }
}
