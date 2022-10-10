<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Entity\WebOrder;
use App\Channels\AliExpress\AliExpressStockParent;

class AliExpressStock extends AliExpressStockParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }
}
