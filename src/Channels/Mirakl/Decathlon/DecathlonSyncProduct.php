<?php

namespace App\Channels\Mirakl\Decathlon;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;
use League\HTMLToMarkdown\HtmlConverter;

class DecathlonSyncProduct extends MiraklSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
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
        $flatProduct = [
            'ProductIdentifier' => $product['identifier'],
            'ean_codes' => $this->getAttributeSimple($product, 'ean'),
            'main_image' => $this->getAttributeSimple($product, 'image_url_1'),
            'mainTitle' => $this->getAttributeSimple($product, 'erp_name'),
        ];


        $familyPim =$product['family'];

        if($familyPim == 'solar_panel') {
            $categoryCode = '30061';
            $flatProduct ['PRODUCT_TYPE'] = "solar panel";
        } elseif($familyPim == 'power_station') {
            $categoryCode = '30060';
            $flatProduct ['PRODUCT_TYPE'] = "power bank";
        } elseif($familyPim == 'robot_piscine') {
            $categoryCode = 'N-1148912';
            $flatProduct ['PRODUCT_TYPE'] = "aspirateur piscine";
            $flatProduct ['SPORT_69'] = "50";
        } elseif($familyPim == 'projector') {
            $categoryCode = '10309';
            $flatProduct ['PRODUCT_TYPE'] = "26258";
            $flatProduct ['SPORT_6'] = "191";
        } elseif($familyPim == 'camera') {
            $categoryCode = '30041';
            $flatProduct ['PRODUCT_TYPE'] = "25201";
        } elseif($familyPim == 'accessories_camera') {
            $categoryCode = 'N-300351';
        }
        $flatProduct ['category'] = $categoryCode;

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
                        $converter = new HtmlConverter();
                        $valueFormate = str_replace(['~', '<hr>', '<hr/>'], ['-', '<hr><p></p>', '<hr><p></p>'], $value);
                        $description = $converter->convert($valueFormate);
                        
                        if (strlen($description)>5000) {
                            $description= substr($description, 0, 5000);
                        }
                        $flatProduct[$localizableMirakl.'-'.$loc] = $description;
                    } elseif ($localizableMirakl=='productTitle') {
                        $flatProduct[$localizableMirakl.'-'.$loc] = substr($this->sanitizeHtml($value), 0, 80);
                    } elseif ($localizableMirakl=='webcatchline') {
                        $flatProduct[$localizableMirakl.'-'.$loc] = substr($this->sanitizeHtml($value), 0, 200);
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
                if ($value) {
                    $codeMirakl = $this->getCodeMarketplace($categoryCode, $fieldMirakl, $value);
                    if ($codeMirakl) {
                        $flatProduct[$fieldMirakl] = $codeMirakl;
                    }
                }
            } elseif ($fieldPim['type']=='choice') {
                $value = $this->getAttributeChoice($product, $fieldPim['field'], $fieldPim['locale']);
                if ($value) {
                    $codeMirakl = $this->getCodeMarketplace($categoryCode, $fieldMirakl, $value);
                    if ($codeMirakl) {
                        $flatProduct[$fieldMirakl] = $codeMirakl;
                    }
                }
            } elseif ($fieldPim['type']=='multichoice') {
                $values = $this->getAttributeMultiChoice($product, $fieldPim['field'], $fieldPim['locale']);
                if (count($values)>0) {
                    $valuesMirakls= [];
                    foreach ($values as $value) {
                        $codeMirakl = $this->getCodeMarketplace($categoryCode, $fieldMirakl, $value);
                        if ($codeMirakl) {
                            $flatProduct[$fieldMirakl] = $codeMirakl;
                        }
                    }
                    if (count($valuesMirakls)>0) {
                        $flatProduct[$fieldMirakl] = implode('|', $valuesMirakls);
                    }
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
