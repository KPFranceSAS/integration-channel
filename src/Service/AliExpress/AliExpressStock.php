<?php

namespace App\Service\AliExpress;

use App\Entity\WebOrder;
use App\Helper\Stock\AliExpressStockParent;

class AliExpressStock extends AliExpressStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }
}
