<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Entity\WebOrder;
use App\Channels\AliExpress\AliExpressIntegratorParent;

class AliExpressIntegrateOrder extends AliExpressIntegratorParent
{
    public const ALIEXPRESS_CUSTOMER_NUMBER = "002355";


    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }


    protected function getClientNumber()
    {
        return AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER;
    }
}
