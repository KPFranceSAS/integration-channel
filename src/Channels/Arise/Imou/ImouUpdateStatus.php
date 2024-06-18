<?php

namespace App\Channels\Arise\Imou;

use App\Channels\Arise\AriseUpdateStatusParent;
use App\Entity\IntegrationChannel;

class ImouUpdateStatus extends AriseUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }
}
