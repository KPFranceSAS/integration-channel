<?php

namespace App\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class MediaMarktApi extends MiraklApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $projectDir,
        $mediaMarktClientUrl,
        $mediaMarktClientKey
    ) {
        parent::__construct($logger, $projectDir, $mediaMarktClientUrl, $mediaMarktClientKey);
    }
}
