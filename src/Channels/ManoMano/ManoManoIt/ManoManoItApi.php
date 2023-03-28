<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\Channels\ManoMano\ManoManoApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class ManoManoItApi extends ManoManoApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $manoManoClientUrl,
        $manoManoItContractId,
        $manoManoItKey
    ) {
        parent::__construct($logger, $manoManoClientUrl, $manoManoItKey, $manoManoItContractId);
    }
}
