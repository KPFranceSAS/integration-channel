<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class IniaSluConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '7591001';
    }



    public function getCompanyIntegration()
    {
        return BusinessCentralConnector::INIA;
    }
}
