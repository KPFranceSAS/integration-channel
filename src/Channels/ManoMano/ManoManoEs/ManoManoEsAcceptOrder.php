<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\Channels\ManoMano\ManoManoAcceptOrder;
use App\Entity\IntegrationChannel;


class ManoManoEsAcceptOrder extends ManoManoAcceptOrder
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
   }



}
