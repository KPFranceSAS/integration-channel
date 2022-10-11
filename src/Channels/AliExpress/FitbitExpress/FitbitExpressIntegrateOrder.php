<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressIntegratorParent;
use App\Entity\IntegrationChannel;

class FitbitExpressIntegrateOrder extends AliExpressIntegratorParent
{
    public const FITBITEXPRESS_CUSTOMER_NUMBER = "003253";


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITEXPRESS;
    }


    protected function getClientNumber()
    {
        return FitbitExpressIntegrateOrder::FITBITEXPRESS_CUSTOMER_NUMBER;
    }
}
