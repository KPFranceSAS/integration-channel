<?php

namespace App\Channels\Shopify\PaxEu;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\PaxUk\PaxHelper;
use App\Channels\Shopify\ShopifyIntegrateOrder;
use App\Entity\IntegrationChannel;

class PaxEuIntegrateOrder extends ShopifyIntegrateOrder
{
    final public const PAXFR_CUSTOMER_NUMBER = "KPF01278";
    final public const PAXES_CUSTOMER_NUMBER = "KPF01282";
    final public const PAXIT_CUSTOMER_NUMBER = "KPF01281";
    final public const PAXDE_CUSTOMER_NUMBER = "KPF01280";

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PAXEU;
    }



    public function getCustomerBC($orderApi)
    {
        $countryCode = $orderApi['shipping_address']['country_code'];
        if ($countryCode =='ES') {
            return self::PAXES_CUSTOMER_NUMBER;
        } elseif ($countryCode =='IT') {
            return self::PAXIT_CUSTOMER_NUMBER;
        } elseif ($countryCode =='DE') {
            return self::PAXDE_CUSTOMER_NUMBER;
        } else {
            return self::PAXFR_CUSTOMER_NUMBER;
        }
    }


    protected function getSuffix()
    {
        return 'PAX-EUR-';
    }

    protected function getLabelCurrency()
    {
        return 'EUR';
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KP_FRANCE;
    }


    protected function getProductCorrelationSku(string $sku, $company): string
    {
        return PaxHelper::getBusinessCentralSku($sku);
    }
}
