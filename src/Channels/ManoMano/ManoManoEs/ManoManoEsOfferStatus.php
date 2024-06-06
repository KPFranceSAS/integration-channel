<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\Channels\ManoMano\ManoManoOfferStatusParent;
use App\Entity\IntegrationChannel;


class ManoManoEsOfferStatus extends ManoManoOfferStatusParent
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
   }

}