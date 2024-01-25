<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\ManoMano\ManoManoIntegratorParent;
use App\Entity\IntegrationChannel;

class ManoManoFrIntegrator extends ManoManoIntegratorParent
{
   final public const MANOMANO_FR = '000783';



    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MANOMANO_FR;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::MANOMANO_FR;
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }
}
