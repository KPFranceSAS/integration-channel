<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoAcceptOrder;
use App\Entity\IntegrationChannel;


class ManoManoFrAcceptOrder extends ManoManoAcceptOrder
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
   }



}
