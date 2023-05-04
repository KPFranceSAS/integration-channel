<?php

namespace App\Channels\Mirakl\Boulanger;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class BoulangerIntegrator extends MiraklIntegratorParent
{
    public const BOULANGER_FR = '000820';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::BOULANGER_FR;
    }



    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }

    protected function getExternalNumber($orderApi)
    {
        return  $orderApi['id'];
    }
}
