<?php

namespace App\Channels\ManoMano\ManoManoDe;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\ManoMano\ManoManoIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class ManoManoDeIntegrator extends ManoManoIntegratorParent
{
    public const MANOMANO_DE = '000782';


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_DE;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::MANOMANO_DE;
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }
}
