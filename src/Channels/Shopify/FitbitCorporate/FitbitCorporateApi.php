<?php

namespace App\Channels\Shopify\FitbitCorporate;

use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\IntegrationChannel;
use Psr\Log\LoggerInterface;

class FitbitCorporateApi extends ShopifyApiParent
{
    public function __construct(LoggerInterface $logger, $fitbitCorporateToken, $fitbitCorporateClientId, $fitbitCorporateClientSecret, $fitbitCorporateShopDomain, $fitbitCorporateVersion, $fitbitCorporateScopes)
    {
        parent::__construct($logger, $fitbitCorporateToken, $fitbitCorporateClientId, $fitbitCorporateClientSecret, $fitbitCorporateShopDomain, $fitbitCorporateVersion, $fitbitCorporateScopes);
    }

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }
}
