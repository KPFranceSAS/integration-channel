<?php

namespace App\Service\AliExpress;

use App\Entity\WebOrder;
use App\Helper\Api\AliExpressApiParent;
use Psr\Log\LoggerInterface;

class AliExpressApi extends AliExpressApiParent
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }


    public function __construct(LoggerInterface $logger, $aliExpressClientId, $aliExpressClientSecret, $aliExpressClientAccessToken)
    {
        parent::__construct($logger, $aliExpressClientId, $aliExpressClientSecret, $aliExpressClientAccessToken);
    }
}
