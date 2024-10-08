<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class KpFranceConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '758000';
    }


    


    public function getCompanyIntegration()
    {
        return self::KP_FRANCE;
    }
}
