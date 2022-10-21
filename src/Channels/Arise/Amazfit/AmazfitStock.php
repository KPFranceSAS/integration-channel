<?php

namespace App\Channels\Arise\Amazfit;

use App\Channels\Arise\AriseStockParent;
use App\Entity\IntegrationChannel;

class AmazfitStock extends AriseStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
