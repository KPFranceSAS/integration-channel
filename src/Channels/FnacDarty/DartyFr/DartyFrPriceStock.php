<?php

namespace App\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\FnacDartyPriceStock;
use App\Entity\IntegrationChannel;

class DartyFrPriceStock extends FnacDartyPriceStock
{

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DARTY_FR;
    }

}
