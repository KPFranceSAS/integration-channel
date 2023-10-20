<?php

namespace App\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MiraklUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class MediaMarktUpdateDelivery extends MiraklUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }
}
