<?php

namespace App\Channels\Mirakl\Decathlon;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class DecathlonSyncProduct extends MiraklSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('arise_category_id', 'NOT EMPTY')
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('marketplaces_assignement', 'IN', ['arise_decathlon_es_gi'])
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }

    protected function getLocale()
    {
        return 'es_ES';
    }
}
