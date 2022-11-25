<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpressPriceParent;
use App\Entity\IntegrationChannel;

class AliExpressPrice extends AliExpressPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }
}
