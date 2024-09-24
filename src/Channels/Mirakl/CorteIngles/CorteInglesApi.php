<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class CorteInglesApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $projectDir,
        $corteInglesClientUrl,
        $corteInglesClientKey
    ) {
        parent::__construct($logger, $projectDir, $corteInglesClientUrl, $corteInglesClientKey);
    }
}
