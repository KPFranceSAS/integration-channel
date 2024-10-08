<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KpUkConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '00101';
    }



    public function getCompanyIntegration()
    {
        return self::KP_UK;
    }
}
