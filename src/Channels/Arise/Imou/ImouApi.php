<?php

namespace App\Channels\Arise\Imou;

use App\Channels\Arise\AriseApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class ImouApi extends AriseApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }


    public function __construct(
        LoggerInterface $logger,
        $imouClientId,
        $imouClientSecret,
        $imouClientAccessToken
    ) {
        parent::__construct($logger, $imouClientId, $imouClientSecret, $imouClientAccessToken);
    }
}
