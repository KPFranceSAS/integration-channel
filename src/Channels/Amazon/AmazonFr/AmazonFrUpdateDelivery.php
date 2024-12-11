<?php

namespace App\Channels\Amazon\AmazonFr;

use App\Channels\Amazon\AmazonUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class AmazonFrUpdateDelivery extends AmazonUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZON_FR;
    }
}
