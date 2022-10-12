<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KpUkConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '758000';
    }


    protected function getCompanyIntegration()
    {
        return self::KP_UK;
    }
}
