<?php

namespace App\Service\FitbitExpress;

use App\Entity\WebOrder;
use App\Helper\Api\AliExpressApiParent;
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
