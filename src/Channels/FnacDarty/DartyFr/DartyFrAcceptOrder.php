<?php

namespace App\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\FnacDartyAcceptOrder;
use App\Entity\IntegrationChannel;

class DartyFrAcceptOrder extends FnacDartyAcceptOrder
{

    public function getChannel() : string
    {
        return IntegrationChannel::CHANNEL_DARTY_FR;
    }



}
