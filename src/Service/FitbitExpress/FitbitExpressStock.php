<?php

namespace App\Service\FitbitExpress;

use App\Entity\WebOrder;
use App\Helper\Stock\AliExpressStockParent;

class FitbitExpressStock  extends AliExpressStockParent
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
