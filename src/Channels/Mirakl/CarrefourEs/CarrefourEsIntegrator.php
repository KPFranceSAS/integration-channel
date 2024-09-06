<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class CarrefourEsIntegrator extends MiraklIntegratorParent
{
    final public const CARREFOUR_ES = 'KP135737';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::CARREFOUR_ES;
    }


    protected function getExternalNumber($orderApi)
    {
        return  $orderApi['id'];
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
