<?php

namespace App\Channels\Shopify\Minibatt;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyApiParent;
use Psr\Log\LoggerInterface;

class MinibattApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $minibattToken, $minibattClientId, $minibattClientSecret, $minibattShopDomain, $minibattVersion, $minibattScopes)
    {
        parent::__construct($logger, $minibattToken, $minibattClientId, $minibattClientSecret, $minibattShopDomain, $minibattVersion, $minibattScopes);
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_MINIBATT;
    }
}
