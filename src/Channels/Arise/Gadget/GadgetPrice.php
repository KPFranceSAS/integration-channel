<?php

namespace App\Channels\Arise\Gadget;

use App\Channels\Arise\ArisePriceParent;
use App\Entity\IntegrationChannel;

class GadgetPrice extends ArisePriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }
}
