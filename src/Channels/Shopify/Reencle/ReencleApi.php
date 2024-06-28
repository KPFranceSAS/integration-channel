<?php

namespace App\Channels\Shopify\Reencle;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class ReencleApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $reencleToken, $reencleClientId, $reencleClientSecret, $reencleShopDomain, $reencleVersion, $reencleScopes)
    {
        parent::__construct($logger, $reencleToken, $reencleClientId, $reencleClientSecret, $reencleShopDomain, $reencleVersion, $reencleScopes);
    }

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_REENCLE;
    }
}
