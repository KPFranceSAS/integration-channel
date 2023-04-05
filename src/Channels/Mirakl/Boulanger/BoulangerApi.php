<?php

namespace App\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class BoulangerApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $projectDir,
        $boulangerClientUrl,
        $boulangerClientKey
    ) {
        parent::__construct($logger, $projectDir, $boulangerClientUrl, $boulangerClientKey);
    }
}
