<?php

namespace App\Channels\Amazon\AmazonFr;

use App\Channels\Amazon\AmazonPriceStockParent;
use App\Entity\IntegrationChannel;

class AmazonFrPriceStock extends AmazonPriceStockParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_AMAZON_FR;
    }


    protected function getCountryCode(){
        return 'FR';
    }
}
