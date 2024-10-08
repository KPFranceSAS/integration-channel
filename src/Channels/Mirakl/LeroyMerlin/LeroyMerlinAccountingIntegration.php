<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklAccountingIntegrationParent;
use App\Entity\IntegrationChannel;

class LeroyMerlinAccountingIntegration extends MiraklAccountingIntegrationParent
{

    public const VENDOR_NUMBER_LEROYMERLIN = "20544";


    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }


    protected function getBankNumber(): string
    {
        return '2024';
    }


    protected function getByDefaultCustomer(): string
    {
        return LeroyMerlinIntegrator::LEROYMERLIN_FR;
    }

    protected function getJournalName(): string
    {
        return 'LEROY';
    }


    protected function getAccountNumberForFeesMarketplace(): string
    {
        return '604008';
    }


    protected function getCompanyIntegration() :  string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }


    protected function getProviderNumber(): string
    {
        return self::VENDOR_NUMBER_LEROYMERLIN;
    }



}
