<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\Channels\ManoMano\ManoManoPriceStockParent;
use App\Entity\IntegrationChannel;

class ManoManoItPriceStock extends ManoManoPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
    }



    protected function getCountryCode(){
        return 'IT';
    }
}
