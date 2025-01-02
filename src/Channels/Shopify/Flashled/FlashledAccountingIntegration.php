<?php

namespace App\Channels\Shopify\Flashled;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Shopify\Flashled\FlashledIntegrateOrder;
use App\Channels\Shopify\ShopifyAccountingIntegrationParent;
use App\Entity\IntegrationChannel;

class FlashledAccountingIntegration extends ShopifyAccountingIntegrationParent
{


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
    }


    protected function getBankNumber(): string
    {
        return '08';
    }

    protected function getBankName(): string
    {
        return "BANCA MARCH, SA(0115)";
    }


    protected function getByDefaultCustomer(): string
    {
        return FlashledIntegrateOrder::FLASHLED_CUSTOMER_NUMBER;
    }

    protected function getJournalName(): string
    {
        return 'FLASHLED';
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
