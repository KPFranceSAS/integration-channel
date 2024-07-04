<?php

namespace App\Channels\Mirakl\Worten;

use App\Channels\Mirakl\MiraklOfferStatusParent;
use App\Entity\IntegrationChannel;

class WortenOfferStatus extends MiraklOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_WORTEN;
    }

    protected function getIdentifier()
    {
        return 'product_id';
    }

}
