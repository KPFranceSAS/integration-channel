<?php

namespace App\Channels\FnacDarty\DartyFr;


use App\Channels\FnacDarty\FnacDartyOfferStatusParent;
use App\Entity\IntegrationChannel;

class DartyFrOfferStatus extends FnacDartyOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DARTY_FR;
    }

}
