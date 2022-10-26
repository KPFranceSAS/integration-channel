<?php

namespace App\Channels\Arise\Sonos;

use App\Channels\Arise\AriseApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class SonosApi extends AriseApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }


    public function __construct(
        LoggerInterface $logger,
        $sonosClientId,
        $sonosClientSecret,
        $sonosClientAccessToken
    ) {
        parent::__construct($logger, $sonosClientId, $sonosClientSecret, $sonosClientAccessToken);
    }
}
