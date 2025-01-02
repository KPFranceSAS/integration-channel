<?php

namespace App\Channels\Amazon\AmazonFr;

use AmazonPHP\SellingPartner\Marketplace;
use App\Channels\Amazon\AmazonApiParent;
use App\Entity\IntegrationChannel;

class AmazonFrApi extends AmazonApiParent
{
    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZON_FR;
    }


    public function getMarketplaceId()
    {
        return Marketplace::fromCountry('ES')->id();
    }
    
}
