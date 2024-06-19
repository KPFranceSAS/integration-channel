<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoPriceStockParent;
use App\Entity\IntegrationChannel;

class ManoManoDePriceStock extends ManoManoPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
    }


    protected function getCountryCode(){
        return 'DE';
    }
}
