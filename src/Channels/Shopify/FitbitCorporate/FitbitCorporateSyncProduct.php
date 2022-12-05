<?php

namespace App\Channels\Shopify\FitbitCorporate;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Shopify\ShopifySyncProductParent;
use App\Entity\IntegrationChannel;

class FitbitCorporateSyncProduct extends ShopifySyncProductParent
{
    protected function getNbLevels()
    {
        return 2;
    }

    protected function getCategoryTree()
    {
        return 'fitbit';
    }


    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', ['fitbit'])
            ->addFilter('enabled_channel', '=', true, ['scope' => 'fitbit'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'fitbit');
    }

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_FITBITCORPORATE;
    }

    protected function getLocale()
    {
        return 'es_ES';
    }
}
