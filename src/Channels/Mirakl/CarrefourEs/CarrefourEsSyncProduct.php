<?php

namespace App\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class CarrefourEsSyncProduct extends MiraklSyncProductParent
{



    protected function flatProduct(array $product):array
    {
        $flatProduct = parent::flatProduct($product);
        if (array_key_exists('description-es_ES-Marketplace', $product)) {
            $flatProduct['description-es_ES-Marketplace'] = substr($flatProduct['description-es_ES-Marketplace'], 0, 3500);
        }

        return $flatProduct;

    }

    
    protected function getMarketplaceNode(): string
    {
        return 'carrefourEs';
    }



    public function getLocales(): array
    {
        return [
            'es_ES', 'en_GB'
        ];
    }




    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_CARREFOUR_ES;
    }
}
