<?php

namespace App\Channels\Shopify\Minibatt;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class MinibattApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $minibattToken, $minibattClientId, $minibattClientSecret, $minibattShopDomain, $minibattVersion, $minibattScopes)
    {
        parent::__construct($logger, $minibattToken, $minibattClientId, $minibattClientSecret, $minibattShopDomain, $minibattVersion, $minibattScopes);
    }

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MINIBATT;
    }
}
