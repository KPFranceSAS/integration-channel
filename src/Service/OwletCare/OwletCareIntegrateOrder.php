<?php

namespace App\Service\OwletCare;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\Integrator\ShopifyIntegrateOrder;


class OwletCareIntegrateOrder extends ShopifyIntegrateOrder

{

    public const OWLETCARE_CUSTOMER_NUMBER = "130803";

    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }


    public function getCustomerBC($orderApi)
    {
        return self::OWLETCARE_CUSTOMER_NUMBER;
    }


    protected function getSuffix()
    {
        return 'OWL-';
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
