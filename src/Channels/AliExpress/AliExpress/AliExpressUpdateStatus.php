<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpressUpdateStatusParent;
use App\Entity\WebOrder;

class AliExpressUpdateStatus extends AliExpressUpdateStatusParent
{
    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }
}
