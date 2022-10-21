<?php

namespace App\Channels\Arise\Gadget;

use App\Channels\Arise\AriseApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class GadgetApi extends AriseApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_ARISE;
    }


    public function __construct(
        LoggerInterface $logger,
        $ariseClientId,
        $ariseClientSecret,
        $ariseClientRefreshToken
    ) {
        parent::__construct($logger, $ariseClientId, $ariseClientSecret, $ariseClientRefreshToken);
    }
}
