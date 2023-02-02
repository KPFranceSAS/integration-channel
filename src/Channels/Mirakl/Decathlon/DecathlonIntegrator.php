<?php

namespace App\Channels\Mirakl\Decathlon;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class DecathlonIntegrator extends MiraklIntegratorParent
{

    public const DECATHLON_FR = '000774';


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }


    public function getCustomerBC($orderApi): string
    {
        $codeChannel = $orderApi['channel']['code'];
        if($codeChannel == 'FR'){
            return self::DECATHLON_FR;
        } else {
            throw new Exception('No customer has been setup for Decathlon on channel '.$codeChannel);
        }
    }



    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }

}
