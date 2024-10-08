<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class GadgetIberiaConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '7591001';
    }




    public function getCompanyIntegration()
    {
        return BusinessCentralConnector::GADGET_IBERIA;
    }
}
