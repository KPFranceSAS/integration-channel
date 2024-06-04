<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\MiraklOfferStatusParent;
use App\Entity\IntegrationChannel;

class LeroyMerlinOfferStatus extends MiraklOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }

    protected function getIdentifier(){
        return 'shop_sku';
    }

}
