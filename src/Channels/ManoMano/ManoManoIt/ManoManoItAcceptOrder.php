<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\Channels\ManoMano\ManoManoAcceptOrder;
use App\Entity\IntegrationChannel;


class ManoManoItAcceptOrder extends ManoManoAcceptOrder
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
   }

}
