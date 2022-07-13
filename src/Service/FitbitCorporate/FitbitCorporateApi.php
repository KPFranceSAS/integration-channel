<?php

namespace App\Service\FitbitCorporate;

use App\Entity\WebOrder;
use App\Helper\Api\ShopifyApiParent;
use Psr\Log\LoggerInterface;

class FitbitCorporateApi extends ShopifyApiParent
{

    public function __construct(LoggerInterface $logger, $fitbitCorporateToken, $fitbitCorporateClientId, $fitbitCorporateClientSecret, $fitbitCorporateShopDomain, $fitbitCorporateVersion, $fitbitCorporateScopes)
    {
        parent::__construct($logger, $fitbitCorporateToken, $fitbitCorporateClientId, $fitbitCorporateClientSecret, $fitbitCorporateShopDomain, $fitbitCorporateVersion, $fitbitCorporateScopes);
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITCORPORATE;
    }
}
