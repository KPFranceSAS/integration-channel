<?php

namespace App\Channels\Arise\Amazfit;

use App\Channels\Arise\AriseApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class AmazfitApi extends AriseApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }


    public function __construct(
        LoggerInterface $logger,
        $amazfitClientId,
        $amazfitClientSecret,
        $amazfitClientAccessToken
    ) {
        parent::__construct($logger, $amazfitClientId, $amazfitClientSecret, $amazfitClientAccessToken);
    }
}
