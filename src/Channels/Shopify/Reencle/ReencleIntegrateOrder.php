<?php

namespace App\Channels\Shopify\Reencle;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\ShopifyIntegrateOrder;
use App\Entity\IntegrationChannel;

class ReencleIntegrateOrder extends ShopifyIntegrateOrder
{
    final public const REENCLE_CUSTOMER_NUMBER = "KP135682";

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_REENCLE;
    }


    public function getCustomerBC($orderApi)
    {
        return self::REENCLE_CUSTOMER_NUMBER;
    }


    protected function getSuffix()
    {
        return 'RNC-';
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
