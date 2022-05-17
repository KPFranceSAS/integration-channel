<?php

namespace App\Service\OwletCare;

use App\Entity\WebOrder;
use App\Helper\Api\ShopifyApiParent;
use Psr\Log\LoggerInterface;



class OwletCareApi extends ShopifyApiParent
{

    public function __construct(LoggerInterface $logger, $owletCareToken, $owletCareClientId, $owletCareClientSecret, $owletCareShopDomain, $owletCareVersion, $owletCareScopes)
    {
        parent::__construct($logger, $owletCareToken, $owletCareClientId, $owletCareClientSecret, $owletCareShopDomain, $owletCareVersion, $owletCareScopes);
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }
}
