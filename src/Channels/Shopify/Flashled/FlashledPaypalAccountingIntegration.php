<?php

namespace App\Channels\Shopify\Flashled;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\Flashled\FlashledIntegrateOrder;
use App\Channels\Shopify\ShopifyAccountingIntegrationParent;
use App\Channels\Shopify\ShopifyPaypalAccountingIntegrationParent;
use App\Entity\IntegrationChannel;

class FlashledPaypalAccountingIntegration extends ShopifyPaypalAccountingIntegrationParent
{


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
    }


    protected function getBankNumber(): string
    {
        return '12';
    }

    protected function getBankName(): string
    {
        return "Paypal";
    }


    protected function getByDefaultCustomer(): string
    {
        return FlashledIntegrateOrder::FLASHLED_CUSTOMER_NUMBER;
    }

    protected function getJournalName(): string
    {
        //return 'FLASHLED';
        return 'MOLLIE';
    }


    protected function getAccountNumberForFeesMarketplace(): string
    {
        
        //return '6690009';
        return '6690001';
    }


    protected function getCompanyIntegration() :  string
    {
        return BusinessCentralConnector::KIT_PERSONALIZACION_SPORT;
        return BusinessCentralConnector::TURISPORT;
    }




}
