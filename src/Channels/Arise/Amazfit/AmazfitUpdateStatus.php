<?php

namespace App\Channels\Arise\Amazfit;

use App\Channels\Arise\AriseUpdateStatusParent;
use App\Entity\IntegrationChannel;

class AmazfitUpdateStatus extends AriseUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
