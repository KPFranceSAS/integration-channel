<?php

namespace App\Channels\Shopify\PaxEu;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class PaxEuApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $paxEuToken, $paxEuClientId, $paxEuClientSecret, $paxEuShopDomain, $paxEuVersion, $paxEuScopes)
    {
        parent::__construct($logger, $paxEuToken, $paxEuClientId, $paxEuClientSecret, $paxEuShopDomain, $paxEuVersion, $paxEuScopes);
    }


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_PAXEU;
    }
}
