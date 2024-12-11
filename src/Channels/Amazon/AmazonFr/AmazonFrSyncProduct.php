<?php

namespace App\Channels\Amazon\AmazonFr;

use App\Channels\Amazon\AmazonSyncProductParent;
use App\Entity\IntegrationChannel;

class AmazonFrSyncProduct extends AmazonSyncProductParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_AMAZON_FR;
    }


    public function getChannelPim(): string
    {
        return 'amazon_fr_kp';
    }


    public function getLocale(): string
    {
        return 'fr_FR';
    }
}
