<?php

namespace App\Channels\Mirakl\PcComponentes;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class PcComponentesIntegrator extends MiraklIntegratorParent
{
    final public const PCCOMPONENTES_ES = 'KP135699';
    final public const PCCOMPONENTES_PT = 'KP135700';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PCCOMPONENTES;
    }


    public function getCustomerBC($orderApi): string
    {
        $codeChannel = $orderApi['channel']['code'];
        if ($codeChannel == 'WEB_ES') {
            return self::PCCOMPONENTES_ES;
        } elseif ($codeChannel == 'WEB_PT') {
            return self::PCCOMPONENTES_PT;
        } else {
            throw new Exception('No customer has been setup for PcComponentes on channel '.$codeChannel);
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
