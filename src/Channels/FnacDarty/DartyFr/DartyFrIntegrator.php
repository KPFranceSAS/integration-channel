<?php

namespace App\Channels\FnacDarty\DartyFr;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\FnacDarty\FnacDartyIntegratorParent;
use App\Entity\IntegrationChannel;

class DartyFrIntegrator extends FnacDartyIntegratorParent
{
    public const DARTY_FR = 'KPF00865';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DARTY_FR;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::DARTY_FR;
    }


    protected function getExternalNumber($orderApi)
    {
        return  $orderApi['order_id'];
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }
}
