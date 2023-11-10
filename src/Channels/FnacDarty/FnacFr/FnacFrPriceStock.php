<?php

namespace App\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacDartyPriceStock;
use App\Entity\IntegrationChannel;

class FnacFrPriceStock extends FnacDartyPriceStock
{

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FNAC_FR;
    }

}
