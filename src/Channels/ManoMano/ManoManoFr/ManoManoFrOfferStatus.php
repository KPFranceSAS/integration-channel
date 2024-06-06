<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoOfferStatusParent;
use App\Entity\IntegrationChannel;


class ManoManoFrOfferStatus extends ManoManoOfferStatusParent
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
   }



}