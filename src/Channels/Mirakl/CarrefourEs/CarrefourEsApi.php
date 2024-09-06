<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class CarrefourEsApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $projectDir,
        $carrefourEsClientUrl,
        $carrefourEsClientKey
    ) {
        parent::__construct($logger, $projectDir, $carrefourEsClientUrl, $carrefourEsClientKey);
    }
}
