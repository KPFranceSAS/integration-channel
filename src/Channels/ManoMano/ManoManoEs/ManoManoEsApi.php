<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\Channels\ManoMano\ManoManoApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class ManoManoEsApi extends ManoManoApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $manoManoClientUrl,
        $manoManoEsContractId,
        $manoManoEsKey
    ) {
        parent::__construct($logger, $manoManoClientUrl, $manoManoEsKey, $manoManoEsContractId);
    }
}
