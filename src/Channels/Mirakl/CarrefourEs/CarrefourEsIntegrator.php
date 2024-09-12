<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class CarrefourEsIntegrator extends MiraklIntegratorParent
{
    final public const CARREFOUR_ES = 'KP135737';
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }


    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $saleOrder = parent::transformToAnBcOrder($orderApi);
        $saleOrder->sellingPostalAddress->countryLetterCode='ES';
        $saleOrder->shippingPostalAddress->countryLetterCode='ES';
        return $saleOrder;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::CARREFOUR_ES;
    }


    protected function getExternalNumber($orderApi)
    {
        return  $orderApi['id'];
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
    }
}
