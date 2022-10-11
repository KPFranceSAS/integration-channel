<?php

namespace App\Channels\Shopify\OwletCare;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class OwletCareApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $owletCareToken, $owletCareClientId, $owletCareClientSecret, $owletCareShopDomain, $owletCareVersion, $owletCareScopes)
    {
        parent::__construct($logger, $owletCareToken, $owletCareClientId, $owletCareClientSecret, $owletCareShopDomain, $owletCareVersion, $owletCareScopes);
    }


    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_OWLETCARE;
    }
}
