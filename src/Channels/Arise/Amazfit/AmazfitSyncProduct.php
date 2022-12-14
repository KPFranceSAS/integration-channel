<?php

namespace App\Channels\Arise\Amazfit;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Arise\AriseSyncProductParent;
use App\Entity\IntegrationChannel;

class AmazfitSyncProduct extends AriseSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('arise_category_id', 'NOT EMPTY')
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('marketplaces_assignement', 'IN', ['arise_amazfit_es_gi'])
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }

    protected function getLocale()
    {
        return 'es_ES';
    }
}
