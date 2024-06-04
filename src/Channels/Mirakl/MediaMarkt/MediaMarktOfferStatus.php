<?php

namespace App\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MiraklOfferStatusParent;
use App\Entity\IntegrationChannel;

class MediaMarktOfferStatus extends MiraklOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }

    protected function getIdentifier(){
        return 'SHOP_SKU';
    }

}
