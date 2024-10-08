<?php

namespace App\Channels\Mirakl\Decathlon;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklAccountingIntegrationParent;
use App\Entity\IntegrationChannel;

class DecathlonAccountingIntegration extends MiraklAccountingIntegrationParent
{

    public const VENDOR_NUMBER_DECATHLON = "20540";


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }


    protected function getKeyToCheck()
    {
        return 'commercial_id';
    }

    protected function getBankNumber(): string
    {
        return '2024';
    }


    protected function getByDefaultCustomer(): string
    {
        return DecathlonIntegrator::DECATHLON_FR;
    }

    protected function getAccountNumberForFeesMarketplace(): string
    {
        return '604004';
    }

    protected function getJournalName(): string
    {
        return 'DECATH';
    }
    

    protected function getCompanyIntegration() :  string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }


    protected function getProviderNumber(): string
    {
        return self::VENDOR_NUMBER_DECATHLON;
    }



}
