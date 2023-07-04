<?php

namespace App\Channels\Shopify\PaxUk;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class PaxUkApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $paxUkToken, $paxUkClientId, $paxUkClientSecret, $paxUkShopDomain, $paxUkVersion, $paxUkScopes)
    {
        parent::__construct($logger, $paxUkToken, $paxUkClientId, $paxUkClientSecret, $paxUkShopDomain, $paxUkVersion, $paxUkScopes);
    }


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PAXUK;
    }
}
