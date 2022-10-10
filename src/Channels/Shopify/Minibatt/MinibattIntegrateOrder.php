<?php

namespace App\Channels\Shopify\Minibatt;

use App\Entity\WebOrder;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\ShopifyIntegrateOrder;

class MinibattIntegrateOrder extends ShopifyIntegrateOrder
{
    public const MINIBATT_CUSTOMER_NUMBER = "130957";

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
