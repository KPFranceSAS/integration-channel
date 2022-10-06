<?php

namespace App\Service\FitbitCorporate;

use App\Entity\WebOrder;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Helper\Integrator\ShopifyIntegrateOrder;

class FitbitCorporateIntegrateOrder extends ShopifyIntegrateOrder
{
    public const FITBITCORPORATE_CUSTOMER_NUMBER = "130957";

    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITCORPORATE;
    }


    public function getCustomerBC($orderApi)
    {
        return self::FITBITCORPORATE_CUSTOMER_NUMBER;
    }


    protected function getSuffix()
    {
        return 'FBT-';
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
