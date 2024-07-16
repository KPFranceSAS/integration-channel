<?php

namespace App\Channels\Mirakl\PcComponentes;

use App\Channels\Mirakl\MiraklOfferStatusParent;
use App\Entity\IntegrationChannel;

class PcComponentesOfferStatus extends MiraklOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_PCCOMPONENTES;
    }

    protected function getIdentifier()
    {
        return 'product_id';
    }

}
