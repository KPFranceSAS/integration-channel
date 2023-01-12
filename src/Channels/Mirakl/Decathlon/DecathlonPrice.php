<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklPriceParent;
use App\Entity\IntegrationChannel;

class DecathlonPrice extends MiraklPriceParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
