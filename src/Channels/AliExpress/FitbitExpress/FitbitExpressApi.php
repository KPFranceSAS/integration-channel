<?php

namespace App\Channels\AliExpress\FitbitExpress;

use App\Channels\AliExpress\AliExpressApiParent;
use App\Entity\WebOrder;
use Psr\Log\LoggerInterface;

class FitbitExpressApi extends AliExpressApiParent
{
    public function __construct(LoggerInterface $logger, $fitbitExpressClientId, $fitbitExpressClientSecret, $fitbitExpressClientAccessToken)
    {
        parent::__construct($logger, $fitbitExpressClientId, $fitbitExpressClientSecret, $fitbitExpressClientAccessToken);
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
