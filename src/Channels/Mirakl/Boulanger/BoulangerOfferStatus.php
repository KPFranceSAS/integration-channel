<?php

namespace App\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\MiraklOfferStatusParent;
use App\Entity\IntegrationChannel;

class BoulangerOfferStatus extends MiraklOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
    }

    protected function getIdentifier(){
        return 'REF_UNIV';
    }

}
