<?php

namespace App\Service\AliExpress;

use App\Entity\WebOrder;
use App\Helper\Integrator\AliExpressIntegratorParent;

class AliExpressIntegrateOrder extends AliExpressIntegratorParent
{

    const ALIEXPRESS_CUSTOMER_NUMBER = "002355";


    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }


    protected function getClientNumber()
    {
        return AliExpressIntegrateOrder::ALIEXPRESS_CUSTOMER_NUMBER;
    }
}
