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
            ->addFilter('decathlon_category_id', 'NOT EMPTY')
            ->addFilter('decathlon_product_type', 'NOT EMPTY')
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'decathlon'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'decathlon');
    }

    

    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'category' => $this->getAttributeSimple($product, 'decathlon_category_id'),
            'ProductIdentifier' => $product['identifier'],
            'brandName' => $this->getAttributeChoice($product, 'brand', 'en_GB'),
            'PRODUCT_TYPE' => $this->getAttributeSimple($product, 'decathlon_product_type'),
            'ean_codes' => $this->getAttributeSimple($product, 'ean'),
            'mainImage' => $this->getAttributeSimple($product, 'image_url_1'),
            'mainTitle' => $this->getAttributeSimple($product, 'article_name', 'en_GB'),
            /*'color'=>
            "SIZE" */
        ];


        for ($i = 2; $i <= 7;$i++) {
            $flatProduct['image_'.$i] = $this->getAttributeSimple($product, 'image_url_'.$i);
        }


        $locales = ['en_GB', 'de_DE', 'it_IT', 'fr_FR', 'es_ES'];


        $localizablesTextFields= [
            'productTitle' => 'article_name',
            'webcatchline' => 'short_description',
            'longDescription' => 'description',
            'storageAdvice' => 'storage_advice',
            'video1' => 'howto_video_url_1',
        ];
        


        foreach ($localizablesTextFields as $localizableMirakl => $localizablePim) {
            foreach ($locales as $loc) {
                $value = $this->getAttributeSimple($product, $localizablePim, $loc);
                if ($value) {
                    if ($localizableMirakl=='longDescription') {
                        $flatProduct[$localizableMirakl.'-'.$loc] = $this->convertHtmlToMarkdown($value);
                    } else {
                        $flatProduct[$localizableMirakl.'-'.$loc] = $this->sanitizeHtml($value);
                    }
                }
            }
        }



        $valuesUnit = [
            "CHARACTERISTIC_575" => [
                "field" => 'product_lenght',
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "CHARACTERISTIC_398" => [
                "field" => 'product_width',
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "CHARACTERISTIC_569" => [
                "field" => 'product_height',
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "CHARACTERISTIC_590" => [
                "field" => 'product_weight',
                "unit" => 'KILOGRAM',
                "convertUnit" => 'kg',
                'round' => 3
            ],
         ];

        foreach ($valuesUnit as $valueUnitMirakl=>$valueUnit) {
            $value = $this->getAttributeUnit($product, $valueUnit['field'], $valueUnit['unit'], $valueUnit['round']);
            if ($value) {
                $flatProduct[$valueUnitMirakl]=str_replace('.', ',', $value).' '.$valueUnit['convertUnit'];
            }
        }
        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
