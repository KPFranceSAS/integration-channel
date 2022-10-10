<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressUpdateStatusParent;
use App\Entity\WebOrder;

class FitbitExpressUpdateStatus extends AliExpressUpdateStatusParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
