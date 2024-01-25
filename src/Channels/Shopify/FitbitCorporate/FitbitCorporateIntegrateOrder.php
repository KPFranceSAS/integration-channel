<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\ShopifyIntegrateOrder;
use App\Entity\IntegrationChannel;

class FitbitCorporateIntegrateOrder extends ShopifyIntegrateOrder
{
    final public const FITBITCORPORATE_CUSTOMER_NUMBER = "131157";

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
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
