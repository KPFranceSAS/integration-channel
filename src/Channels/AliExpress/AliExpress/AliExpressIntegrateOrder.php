<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpressIntegratorParent;
use App\Entity\IntegrationChannel;

class AliExpressIntegrateOrder extends AliExpressIntegratorParent
{
    public const ALIEXPRESS_CUSTOMER_NUMBER = "002355";


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }


    protected function getClientNumber()
    {
        return AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER;
    }
}
