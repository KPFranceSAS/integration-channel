<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoAcceptOrder;
use App\Entity\IntegrationChannel;


class ManoManoDeAcceptOrder extends ManoManoAcceptOrder
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
   }



}
