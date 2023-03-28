<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class ManoManoDeApi extends ManoManoApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
    }

    
    public function __construct(
        LoggerInterface $logger,
        $manoManoClientUrl,
        $manoManoDeContractId,
        $manoManoDeKey
    ) {
        parent::__construct($logger, $manoManoClientUrl, $manoManoDeKey, $manoManoDeContractId);
    }
}
