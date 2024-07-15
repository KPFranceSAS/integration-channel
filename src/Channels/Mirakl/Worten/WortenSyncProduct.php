<?php

namespace App\Channels\Mirakl\Worten;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class WortenSyncProduct extends MiraklSyncProductParent
{


    
    protected function getMarketplaceNode(): string
    {
        return 'worten';
    }



    public function getLocales(): array
    {
        return [
            'es_ES', 'pt_PT', 'en_GB'
        ];
    }




    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_WORTEN;
    }
}
