<?php

namespace App\Channels\Shopify\PaxUk;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\Channels\Shopify\PaxUk\PaxHelper;
use App\Channels\Shopify\ShopifyIntegrateOrder;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class PaxUkIntegrateOrder extends ShopifyIntegrateOrder
{
    public const PAXUK_CUSTOMER_NUMBER = "KPU000020";

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PAXUK;
    }



    public function getCustomerBC($orderApi)
    {
        return self::PAXUK_CUSTOMER_NUMBER;
    }


    protected function getSuffix()
    {
        return 'PAX-UK-';
    }

    protected function getLabelCurrency()
    {
        return 'GBP';
    }


    public function getCompanyIntegration($orderApi)
    {
        return BusinessCentralConnector::KP_UK;
    }


    protected function getProductCorrelationSku(string $sku, $company): string
    {
        return PaxHelper::getBusinessCentralSku($sku);
    }
}
