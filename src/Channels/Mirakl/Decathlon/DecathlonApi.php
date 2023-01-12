<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class DecathlonApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $decathlonClientUrl,
        $decathlonClientKey
    ) {
        parent::__construct($logger, $decathlonClientUrl, $decathlonClientKey);
    }
}
