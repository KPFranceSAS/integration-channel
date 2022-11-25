<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressPriceParent;
use App\Entity\IntegrationChannel;

class FitbitExpressPrice extends AliExpressPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FITBITEXPRESS;
    }
}
