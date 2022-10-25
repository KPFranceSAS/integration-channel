<?php

namespace App\Channels\Arise\Amazfit;

use App\Channels\Arise\ArisePriceParent;
use App\Entity\IntegrationChannel;

class AmazfitPrice extends ArisePriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }
}
