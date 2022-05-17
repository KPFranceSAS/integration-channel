<?php

namespace App\Service\Minibatt;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\Integrator\ShopifyIntegrateOrder;


class MinibattIntegrateOrder extends ShopifyIntegrateOrder

{

    const MINIBATT_CUSTOMER_NUMBER = "130803";

    public function getChannel()
    {
        return WebOrder::CHANNEL_MINIBATT;
    }


    public function getCustomerBC($orderApi)
    {
        return self::MINIBATT_CUSTOMER_NUMBER;
    }


    protected function getSuffix()
    {
        return 'MNB-';
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
