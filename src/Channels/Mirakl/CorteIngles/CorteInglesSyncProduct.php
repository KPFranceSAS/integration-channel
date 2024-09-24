<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class CorteInglesSyncProduct extends MiraklSyncProductParent
{


    
    protected function getMarketplaceNode(): string
    {
        return 'corteIngles';
    }



    public function getLocales(): array
    {
        return [
            'es_ES', 'pt_PT', 'en_GB'
        ];
    }




    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
    }
}
