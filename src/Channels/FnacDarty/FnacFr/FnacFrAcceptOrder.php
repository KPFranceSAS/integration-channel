<?php

namespace App\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacDartyAcceptOrder;
use App\Entity\IntegrationChannel;


class FnacFrAcceptOrder extends FnacDartyAcceptOrder
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_FNAC_FR;
   }



}
