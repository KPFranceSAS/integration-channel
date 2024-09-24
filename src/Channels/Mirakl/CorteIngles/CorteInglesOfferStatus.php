<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\MiraklOfferStatusParent;
use App\Entity\IntegrationChannel;

class CorteInglesOfferStatus extends MiraklOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
    }

    protected function getIdentifier()
    {
        return 'product_id';
    }

}
