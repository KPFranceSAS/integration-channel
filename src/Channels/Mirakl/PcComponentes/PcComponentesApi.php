<?php

namespace App\Channels\Mirakl\PcComponentes;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class PcComponentesApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PCCOMPONENTES;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $projectDir,
        $pcComponentesClientUrl,
        $pcComponentesClientKey
    ) {
        parent::__construct($logger, $projectDir, $pcComponentesClientUrl, $pcComponentesClientKey);
    }
}
