<?php

namespace App\Channels\FnacDarty\FnacFr;


use App\Channels\FnacDarty\FnacDartyOfferStatusParent;
use App\Entity\IntegrationChannel;

class FnacFrOfferStatus extends FnacDartyOfferStatusParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FNAC_FR;
    }

}
