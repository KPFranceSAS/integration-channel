<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KitPersonalizacionSportConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '7591001';
    }




    public function getCompanyIntegration()
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
