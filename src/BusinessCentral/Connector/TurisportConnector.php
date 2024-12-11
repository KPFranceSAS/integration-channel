<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class TurisportConnector extends BusinessCentralConnector
{
    public function getCompanyIntegration()
    {
        return BusinessCentralConnector::TURISPORT;
    }


    public function getAccountNumberForExpedition()
    {
        return '7591001';
    }

}
