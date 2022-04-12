<?php

namespace App\Service\FitbitExpress;

use App\Service\AliExpress\AliExpressApi;
use Psr\Log\LoggerInterface;


class FitbitExpressApi extends AliExpressApi
{

    public function __construct(LoggerInterface $logger, $fitbitExpressClientId, $fitbitExpressClientSecret, $fitbitExpressClientAccessToken)
    {
        parent::__construct($logger, $fitbitExpressClientId, $fitbitExpressClientSecret, $fitbitExpressClientAccessToken);
    }
}
