<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class LeroyMerlinIntegrator extends MiraklIntegratorParent
{
    final public const LEROYMERLIN_FR = '000802';
    final public const LEROYMERLIN_ES = '000803';
    final public const LEROYMERLIN_IT = '000816';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }


    public function getCustomerBC($orderApi): string
    {
        $codeChannel = $orderApi['channel']['code'];
        if ($codeChannel == '001') {
            return self::LEROYMERLIN_FR;
        } elseif ($codeChannel == '002') {
            return self::LEROYMERLIN_ES;
        } elseif ($codeChannel == '005') {
            return self::LEROYMERLIN_IT;
        } else {
            throw new Exception('No customer has been setup for LeroyMerlin on channel '.$codeChannel);
        }
    }


    protected function getExternalNumber($orderApi)
    {
        return  $orderApi['id'];
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }
}
