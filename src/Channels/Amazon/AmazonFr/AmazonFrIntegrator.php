<?php

namespace App\Channels\Amazon\AmazonFr;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Amazon\AmazonIntegratorParent;
use App\Entity\IntegrationChannel;

class AmazonFrIntegrator extends AmazonIntegratorParent
{
   final public const AMAZON_FR = '000783';



    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZON_FR;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::AMAZON_FR;
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }
}
