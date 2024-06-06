<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoOfferStatusParent;
use App\Entity\IntegrationChannel;


class ManoManoDeOfferStatus extends ManoManoOfferStatusParent
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
   }

}