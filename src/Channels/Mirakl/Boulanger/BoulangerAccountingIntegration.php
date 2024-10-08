<?php

namespace App\Channels\Mirakl\Boulanger;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklAccountingIntegrationParent;
use App\Entity\IntegrationChannel;

class BoulangerAccountingIntegration extends MiraklAccountingIntegrationParent
{

    public const VENDOR_NUMBER_BOULANGER = "20564";


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
    }


    protected function getAccountNumberForFeesMarketplace(): string
    {
        return '604006';
    }


    protected function getKeyToCheck(): string
    {
        return 'id';
    }

    protected function getBankNumber(): string
    {
        return '2024';
    }


    protected function getJournalName(): string
    {
        return 'BOULA';
    }


    protected function getByDefaultCustomer(): string
    {
        return BoulangerIntegrator::BOULANGER_FR;
    }


    protected function getCompanyIntegration() :  string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }


    protected function getProviderNumber(): string
    {
        return self::VENDOR_NUMBER_BOULANGER;
    }



}
