<?php

namespace App\Channels\Amazon\AmazonFr;

use App\Channels\Amazon\AmazonOfferStatusParent;
use App\Entity\IntegrationChannel;


class AmazonFrOfferStatus extends AmazonOfferStatusParent
{

   public function getChannel() : string {
        return IntegrationChannel::CHANNEL_AMAZON_FR;
   }



}