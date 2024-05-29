<?php

namespace App\Channels\Arise\Sonos;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Arise\AriseSyncProductParent;
use App\Entity\IntegrationChannel;

class SonosSyncProduct extends AriseSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('marketplaces_assignement', 'IN', ['arise_sonos_es_gi'])
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_SONOS_ARISE;
    }

    protected function getLocale()
    {
        return 'es_ES';
    }
}
