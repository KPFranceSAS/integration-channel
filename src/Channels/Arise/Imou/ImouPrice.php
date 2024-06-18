<?php

namespace App\Channels\Arise\Imou;

use App\Channels\Arise\ArisePriceParent;
use App\Entity\IntegrationChannel;

class ImouPrice extends ArisePriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }
}
