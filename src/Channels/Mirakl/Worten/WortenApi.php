<?php

namespace App\Channels\Mirakl\Worten;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class WortenApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_WORTEN;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $projectDir,
        $wortenClientUrl,
        $wortenClientKey
    ) {
        parent::__construct($logger, $projectDir, $wortenClientUrl, $wortenClientKey);
    }
}
