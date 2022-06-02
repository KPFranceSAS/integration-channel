<?php

namespace App\Service\BusinessCentral;

use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;

class KpFranceConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '758000';
    }


    protected function getCompanyIntegration()
    {
        return self::KP_FRANCE;
    }
}
