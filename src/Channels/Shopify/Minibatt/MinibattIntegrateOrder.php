<?php

namespace App\Channels\Shopify\Minibatt;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\ShopifyIntegrateOrder;
use App\Entity\IntegrationChannel;

class MinibattIntegrateOrder extends ShopifyIntegrateOrder
{
    final public const MINIBATT_CUSTOMER_NUMBER = "130957";

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MINIBATT;
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
        return BusinessCentralConnector::TURISPORT;
    }
}
