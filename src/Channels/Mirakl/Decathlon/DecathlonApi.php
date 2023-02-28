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
        $projectDir,
        $decathlonClientUrl,
        $decathlonClientKey
    ) {
        parent::__construct($logger, $projectDir, $decathlonClientUrl, $decathlonClientKey);
    }
}
