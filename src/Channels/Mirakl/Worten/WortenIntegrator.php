<?php

namespace App\Channels\Mirakl\Worten;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class WortenIntegrator extends MiraklIntegratorParent
{
    final public const WORTEN_ES = 'KP135685';
    final public const WORTEN_PT = 'KP135686';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_WORTEN;
    }


    public function getCustomerBC($orderApi): string
    {
        $codeChannel = $orderApi['channel']['code'];
        if ($codeChannel == 'WRT_ES_ONLINE') {
            return self::WORTEN_ES;
        } elseif ($codeChannel == 'WRT_PT_ONLINE') {
            return self::WORTEN_PT;
        } else {
            throw new Exception('No customer has been setup for Worten on channel '.$codeChannel);
        }
    }


    protected function getExternalNumber($orderApi)
    {
        return  $orderApi['id'];
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
