<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\Channels\ManoMano\ManoManoPriceStockParent;
use App\Entity\IntegrationChannel;

class ManoManoEsPriceStock extends ManoManoPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
    }


    protected function getCountryCode(){
        return 'ES';
    }
}
