<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklOfferStatusParent;
use App\Entity\IntegrationChannel;

class DecathlonOfferStatus extends MiraklOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }

    protected function getIdentifier(){
        return 'ProductIdentifier';
    }

}
