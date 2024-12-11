<?php

namespace App\Channels\Amazon\AmazonFr;

use App\Channels\Amazon\AmazonUpdateStatusParent;
use App\Entity\IntegrationChannel;

class AmazonFrUpdateStatus extends AmazonUpdateStatusParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZON_FR;
    }
}
