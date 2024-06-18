<?php

namespace App\Channels\Arise\Imou;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Arise\AriseSyncProductParent;
use App\Entity\IntegrationChannel;

class ImouSyncProduct extends AriseSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('marketplaces_assignement', 'IN', ['arise_imou_es_gi'])
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_IMOU_ARISE;
    }

    protected function getLocale()
    {
        return 'es_ES';
    }
}
