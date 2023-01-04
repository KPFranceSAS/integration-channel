<?php

namespace App\Channels\Arise\Gadget;

use App\Channels\Arise\AriseUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class GadgetUpdateDelivery extends AriseUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }
}
