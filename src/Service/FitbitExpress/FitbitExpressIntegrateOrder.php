<?php

namespace App\Service\FitbitExpress;

use App\Entity\WebOrder;
use App\Helper\Integrator\AliExpressIntegratorParent;

class FitbitExpressIntegrateOrder extends AliExpressIntegratorParent
{

    const FITBITEXPRESS_CUSTOMER_NUMBER = "003253";


    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }


    protected function getClientNumber()
    {
        return FitbitExpressIntegrateOrder::FITBITEXPRESS_CUSTOMER_NUMBER;
    }
}
