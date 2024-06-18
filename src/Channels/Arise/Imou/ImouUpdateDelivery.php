<?php

namespace App\Channels\Arise\Imou;

use App\Channels\Arise\AriseUpdateDeliveryParent;
use App\Entity\IntegrationChannel;

class ImouUpdateDelivery extends AriseUpdateDeliveryParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }
}
