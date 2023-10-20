<?php

namespace App\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MiraklAcceptOrderParent;
use App\Entity\IntegrationChannel;

class MediaMarktAcceptOrder extends MiraklAcceptOrderParent
{
    public function getChannel():string
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }
}
