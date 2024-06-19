<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoPriceStockParent;
use App\Entity\IntegrationChannel;

class ManoManoFrPriceStock extends ManoManoPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
    }


    protected function getCountryCode(){
        return 'FR';
    }
}
