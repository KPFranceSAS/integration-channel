<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressStockParent;
use App\Entity\WebOrder;

class FitbitExpressStock extends AliExpressStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
