<?php

namespace App\Channels\Mirakl\Decathlon;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;
use App\Helper\Utils\StringUtils;

class DecathlonSyncProduct extends MiraklSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('decathlon_category_id', 'NOT EMPTY')
            ->addFilter('decathlon_product_type', 'NOT EMPTY')
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', ['decathlon_fr_kp'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);


        $categoryCode = str_replace('_', '-', $this->getAttributeSimple($product, 'decathlon_category_id'));


        $flatProduct = [
            'category' =>  $categoryCode,
            'ProductIdentifier' => $product['identifier'],
            
            'PRODUCT_TYPE' => $this->getAttributeSimple($product, 'decathlon_product_type'),
            'ean_codes' => $this->getAttributeSimple($product, 'ean'),
            'main_image' => $this->getAttributeSimple($product, 'image_url_1'),
            'mainTitle' => $this->getAttributeSimple($product, 'erp_name'),
        ];










        for ($i = 2; $i <= 7;$i++) {
            $flatProduct['image_'.$i] = $this->getAttributeSimple($product, 'image_url_'.$i);
        }


        $locales = [
            'en_GB',
            'de_DE',
            'it_IT',
            'fr_FR',
            'es_ES'
        ];


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
                    } elseif ($localizableMirakl=='productTitle') {
                        $flatProduct[$localizableMirakl.'-'.$loc] = substr($this->sanitizeHtml($value), 0, 80);
                    } else {
                        $flatProduct[$localizableMirakl.'-'.$loc] = $this->sanitizeHtml($value);
                    }
                }
            }
        }


        $fieldsToConvert = [
            "brandName" => [
                "field" => "brand",
                "type" => "choice",
                "locale" => "en_GB",
            ],

            "color" => [
                "field" => "color_generic",
                "type" => "choice",
                "locale" => "en_GB",
            ],

            "CHARACTERISTIC_575" => [
                "field" => 'product_lenght',
                "type" => "unit",
                "unit" => 'CENTIMETER',
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "CHARACTERISTIC_398" => [
                "field" => 'product_width',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm' ,
                'round' => 0
            ],
            "CHARACTERISTIC_569" => [
                "field" => 'product_height',
                "unit" => 'CENTIMETER',
                "type" => "unit",
                "convertUnit" => 'cm',
                'round' => 0
            ],
            "CHARACTERISTIC_590" => [
                "field" => 'product_weight',
                "unit" => 'KILOGRAM',
                "type" => "unit",
                "convertUnit" => 'kg',
                'round' => 0
            ],
            
         ];

        foreach ($fieldsToConvert as $fieldMirakl => $fieldPim) {
            $value = null;
            if ($fieldPim['type']=='unit') {
                $valueConverted = $this->getAttributeUnit($product, $fieldPim['field'], $fieldPim['unit'], $fieldPim['round']);
                if ($valueConverted) {
                    $value = $valueConverted.' '.$fieldPim['convertUnit'];
                }
            } elseif ($fieldPim['type']=='choice') {
                $value = $this->getAttributeChoice($product, $fieldPim['field'], $fieldPim['locale']);
            }
            if ($value) {
                $codeMirakl = $this->getCodeMarketplace($categoryCode, $fieldMirakl, $value);
                if ($codeMirakl) {
                    $flatProduct[$fieldMirakl] = $codeMirakl;
                }
            }
        }

        
        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
