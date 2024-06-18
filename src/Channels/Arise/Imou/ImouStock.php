<?php

namespace App\Channels\Arise\Imou;

use App\Channels\Arise\AriseStockParent;
use App\Entity\IntegrationChannel;

class ImouStock extends AriseStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }
}
