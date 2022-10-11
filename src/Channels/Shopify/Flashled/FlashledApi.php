<?php

namespace App\Channels\Shopify\Flashled;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class FlashledApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $flashledToken, $flashledClientId, $flashledClientSecret, $flashledShopDomain, $flashledVersion, $flashledScopes)
    {
        parent::__construct($logger, $flashledToken, $flashledClientId, $flashledClientSecret, $flashledShopDomain, $flashledVersion, $flashledScopes);
    }

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FLASHLED;
    }
}
