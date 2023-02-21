<?php

namespace App\Channels\ManoMano\ManoManoFr;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class ManoManoFrIntegrator extends MiraklIntegratorParent
{
    public const MANOMANO_FR = '000774';


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
