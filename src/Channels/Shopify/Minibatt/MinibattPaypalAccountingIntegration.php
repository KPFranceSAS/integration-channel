<?php

namespace App\Channels\Shopify\Minibatt;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\Minibatt\MinibattIntegrateOrder;
use App\Channels\Shopify\ShopifyPaypalAccountingIntegrationParent;
use App\Entity\IntegrationChannel;

class MinibattPaypalAccountingIntegration extends ShopifyPaypalAccountingIntegrationParent
{


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MINIBATT;
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
        return MinibattIntegrateOrder::MINIBATT_CUSTOMER_NUMBER;
    }

    protected function getJournalName(): string
    {
        return 'MINIBATT';
    }


    protected function getAccountNumberForFeesMarketplace(): string
    {
        
       return '6690009';
       
    }


    protected function getCompanyIntegration() :  string
    {
        return BusinessCentralConnector::TURISPORT;
    }




}
