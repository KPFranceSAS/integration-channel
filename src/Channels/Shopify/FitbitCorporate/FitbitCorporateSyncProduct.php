<?php

namespace App\Channels\Shopify\FitbitCorporate;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Shopify\ShopifySyncProductParent;
use App\Entity\IntegrationChannel;

class FitbitCorporateSyncProduct extends ShopifySyncProductParent
{
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



    protected function getMetaFields(){
        return [
            "sku",
            "brand",
            "color",
            "connectivity_technology",
            "power_source_type",
            "app_available",
            "color_generic",
            "battery_type",
            "energy_autonomy",
            "energy_charge_time",
            "charging_method",
            "health_fitness_tracking",
            "smart_functions",
            "activity_tracking",
            "manufacturer_guarantee",
            "waterproof",
            "waterproof_depth",
            "temperature_display",
            "packed_size",
            "display_touchscreen",
            "in_the_box",
            "closure",
            "screen_technology",
            "gps",
            "product_type"
        ];
    }

    protected function createCategory(array $category)
    {
        $this->logger->info('Create category '.$category['code']);
        $categoryToCreate = [
            'body_html' => $category['descriptions'][$this->getLocale()],
            'title' => $category['labels'][$this->getLocale()],
            'handle' =>  $category['code'],
        ];
        $response = $this->getShopifyApi()->createCustomCategory($categoryToCreate);
        return $response->getDecodedBody();
    }


    protected function updateCategory(array $categoryShopify, array $category)
    {
        $this->logger->info('Update category '.$category['code']);
        $categoryToUpdate = [
            'body_html' => $category['descriptions'][$this->getLocale()],
            'title' => $category['labels'][$this->getLocale()],
        ];
        $response = $this->getShopifyApi()->updateCustomCategory($categoryShopify['id'], $categoryToUpdate);
        return $response->getDecodedBody();
    }
}
