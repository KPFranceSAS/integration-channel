<?php

namespace App\Channels\Arise\Amazfit;

use App\Channels\Arise\AriseUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class AmazfitUpdateDelivery extends AriseUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
