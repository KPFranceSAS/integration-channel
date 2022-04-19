<?php

namespace App\Service\FitbitExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressStock;

class FitbitExpressStock  extends AliExpressStock
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
