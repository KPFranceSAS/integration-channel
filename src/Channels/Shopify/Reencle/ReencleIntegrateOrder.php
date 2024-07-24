<?php

namespace App\Channels\Shopify\Reencle;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\ShopifyIntegrateOrder;
use App\Entity\IntegrationChannel;

class ReencleIntegrateOrder extends ShopifyIntegrateOrder
{
    final public const REENCLE_CUSTOMER_NUMBER_ES = "KP135682";
    final public const REENCLE_CUSTOMER_NUMBER_PT = "KP135708";
    final public const REENCLE_CUSTOMER_NUMBER_FR = "KP135709";

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_REENCLE;
    }


    public function getCustomerBC($orderApi)
    {
       $countryCode = $orderApi['shipping_address']['country_code'];
        if($countryCode =='FR'){
            return self::REENCLE_CUSTOMER_NUMBER_FR;
        } elseif($countryCode =='PT'){
            return self::REENCLE_CUSTOMER_NUMBER_PT;
        } else {
            return self::REENCLE_CUSTOMER_NUMBER_ES;
        }

      
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
