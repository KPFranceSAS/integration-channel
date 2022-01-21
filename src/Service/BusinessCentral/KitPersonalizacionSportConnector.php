<?php

namespace App\Service\BusinessCentral;

use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;



class KitPersonalizacionSportConnector extends BusinessCentralConnector
{


    protected function getAccountNumberForExpedition()
    {
        return '7591001';
    }


    protected function getCompanyIntegration()
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
