<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;

class GadgetIberiaConnector extends BusinessCentralConnector
{
    protected function getAccountNumberForExpedition()
    {
        return '7591001';
    }


    protected function getCompanyIntegration()
    {
        return BusinessCentralConnector::GADGET_IBERIA;
    }
}
