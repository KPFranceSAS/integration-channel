<?php

namespace App\Channels\Shopify\Flashled;

use App\Entity\WebOrder;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\ShopifyIntegrateOrder;

class FlashledIntegrateOrder extends ShopifyIntegrateOrder
{
    public const FLASHLED_CUSTOMER_NUMBER = "130956";

    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }


    public function getCustomerBC($orderApi)
    {
        return self::FLASHLED_CUSTOMER_NUMBER;
    }


    protected function getSuffix()
    {
        return 'FLS-';
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
