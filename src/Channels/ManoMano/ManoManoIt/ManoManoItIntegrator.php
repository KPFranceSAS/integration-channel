<?php

namespace App\Channels\ManoMano\ManoManoIt;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\ManoMano\ManoManoIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class ManoManoItIntegrator extends ManoManoIntegratorParent
{
    public const MANOMANO_IT = '000784';


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_IT;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::MANOMANO_IT;
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }
}
