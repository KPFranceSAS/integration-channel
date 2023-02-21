<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class ManoManoFrApi extends ManoManoApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $manoManoClientUrl,
        $manoManoFrContractId,
        $manoManoFrKey
    ) {
        parent::__construct($logger, $manoManoClientUrl, $manoManoFrKey, $manoManoFrContractId);
    }
}
