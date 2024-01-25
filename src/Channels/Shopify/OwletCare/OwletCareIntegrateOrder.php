<?php

namespace App\Channels\Shopify\OwletCare;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\ShopifyIntegrateOrder;
use App\Entity\IntegrationChannel;

class OwletCareIntegrateOrder extends ShopifyIntegrateOrder
{
    final public const OWLETCARE_CUSTOMER_NUMBER = "130803";

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_OWLETCARE;
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
