<?php

namespace App\Channels\Shopify\Flashled;

use App\Entity\WebOrder;
use App\Channels\Shopify\ShopifyApiParent;
use Psr\Log\LoggerInterface;

class FlashledApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $flashledToken, $flashledClientId, $flashledClientSecret, $flashledShopDomain, $flashledVersion, $flashledScopes)
    {
        parent::__construct($logger, $flashledToken, $flashledClientId, $flashledClientSecret, $flashledShopDomain, $flashledVersion, $flashledScopes);
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_FLASHLED;
    }
}
