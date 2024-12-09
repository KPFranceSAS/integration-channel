<?php

namespace App\Channels\ManoMano\ManoManoEs;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\ManoMano\ManoManoIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class ManoManoEsIntegrator extends ManoManoIntegratorParent
{
    final public const MANOMANO_ES = 'KP135806';


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_ES;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::MANOMANO_ES;
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
