<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\Channels\ManoMano\ManoManoOfferStatusParent;
use App\Entity\IntegrationChannel;


class ManoManoItOfferStatus extends ManoManoOfferStatusParent
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
   }

}