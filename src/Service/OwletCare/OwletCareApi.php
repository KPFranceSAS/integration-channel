<?php

namespace App\Service\OwletCare;

use App\Helper\Api\ShopifyApi;
use Psr\Log\LoggerInterface;



class OwletCareApi extends ShopifyApi
{

    public function __construct(LoggerInterface $logger, $owletCareToken, $owletCareClientId, $owletCareClientSecret, $owletCareShopDomain, $owletCareVersion, $owletCareScopes)
    {
        parent::__construct($logger, $owletCareToken, $owletCareClientId, $owletCareClientSecret, $owletCareShopDomain, $owletCareVersion, $owletCareScopes);
    }
}
