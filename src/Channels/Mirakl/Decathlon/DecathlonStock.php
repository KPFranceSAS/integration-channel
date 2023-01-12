<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklStockParent;
use App\Entity\IntegrationChannel;

class DecathlonStock extends MiraklStockParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
