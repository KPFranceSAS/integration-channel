<?php

namespace App\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpressApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class AliExpressApi extends AliExpressApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ALIEXPRESS;
    }


    public function __construct(
        LoggerInterface $logger,
        $aliExpressClientId,
        $aliExpressClientSecret,
        $aliExpressClientAccessToken
    ) {
        parent::__construct($logger, $aliExpressClientId, $aliExpressClientSecret, $aliExpressClientAccessToken);
    }
}
