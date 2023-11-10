<?php

namespace App\Channels\FnacDarty\FnacFr;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\FnacDarty\FnacDartyIntegratorParent;
use App\Entity\IntegrationChannel;

class FnacFrIntegrator extends FnacDartyIntegratorParent
{
    public const FNAC_FR = 'KPF00860';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FNAC_FR;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::FNAC_FR;
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
