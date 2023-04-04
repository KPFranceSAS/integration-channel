<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class LeroyMerlinApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $projectDir,
        $leroyMerlinClientUrl,
        $leroyMerlinClientKey
    ) {
        parent::__construct($logger, $projectDir, $leroyMerlinClientUrl, $leroyMerlinClientKey);
    }
}
