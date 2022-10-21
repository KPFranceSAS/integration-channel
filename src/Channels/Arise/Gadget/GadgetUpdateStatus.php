<?php

namespace App\Channels\Arise\Gadget;

use App\Channels\Arise\AriseUpdateStatusParent;
use App\Entity\IntegrationChannel;

class GadgetUpdateStatus extends AriseUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
