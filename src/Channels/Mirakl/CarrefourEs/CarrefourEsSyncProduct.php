<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class CarrefourEsSyncProduct extends MiraklSyncProductParent
{


    
    protected function getMarketplaceNode(): string
    {
        return 'carrefourEs';
    }



    public function getLocales(): array
    {
        return [
            'es_ES', 'en_GB'
        ];
    }




    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }
}
