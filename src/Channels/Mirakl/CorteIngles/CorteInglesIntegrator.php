<?php

namespace App\Channels\Mirakl\CorteIngles;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class CorteInglesIntegrator extends MiraklIntegratorParent
{
    final public const ECI_ES = '121781';
    final public const ECI_PT = '121781';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CORTEINGLES;
    }

    public function getCustomerBC($orderApi): string
    {
        $codeChannel = $orderApi['channel']['code'];
        if ($codeChannel == 'eciStore') {
            return self::ECI_ES;
        } elseif ($codeChannel == 'portugalStore') {
            return self::ECI_PT;
        } else {
            throw new Exception('No customer has been setup for CorteIngles on channel '.$codeChannel);
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
