<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class LeroyMerlinSyncProduct extends MiraklSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'IN', ['anker'])
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
            'shop_sku' => $product['identifier'],
            'gtin_EAN13' => $this->getAttributeSimple($product, 'ean'),
        ];

        

        


        $familyPim =$product['family'];

        if($familyPim == 'solar_panel') {
            $flatProduct ['product_category'] =  '200431|CHARGEUR_DE_PILE|PILE_ET_CHARGEUR|R03-002-010';
        } elseif($familyPim = 'power_station') {
            $flatProduct ['product_category'] =  "200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005";
            $flatProduct['ATT_15344'] ='LOV_000001'; // included battery
        }



        $locales = ['fr', 'es', 'it'];

        foreach ($locales as $locale) {
            $localePim = $locale.'_'.strtoupper($locale);
            $flatProduct['i18n_'.$locale.'_12963_title'] = $this->getAttributeSimple($product, "article_name", $localePim);
            $flatProduct['i18n_'.$locale.'_01022_longdescription'] = substr($this->getAttributeSimple($product, "description", $localePim), 0, 5000);
    
                
            for ($i = 1; $i <= 5;$i++) {
                $attributeImageLoc = $this->getAttributeSimpleScopable($product, 'image_url_loc_'.$i, 'Marketplace', $localePim);
                $keyArray = $locale == 'fr' ? 'media_'.$i : 'media_'.$i.'_'.$localePim;
                $flatProduct[$keyArray] = $attributeImageLoc ? $attributeImageLoc : $this->getAttributeSimple($product, 'image_url_'.$i);
            }
    
    
            /*$videoFr = $this->getAttributeSimple($product, 'promo_video_url_1', $localePim);
            if($videoFr) {
                $keyArray = $locale == 'fr' ? 'media_5' : 'media_5_'.$localePim;
                $flatProduct['media_5'] = $videoFr;
            }*/
        }

       
        

        $fieldsToConvert = [
            "feature_06575_brand" => [
                "field" => "brand",
                "type" => "choice",
                "locale" => "en_GB",
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
                    $codeMirakl = $this->getCodeMarketplace($flatProduct ['product_category'], $fieldMirakl, $value);
                    if ($codeMirakl) {
                        $flatProduct[$fieldMirakl] = $codeMirakl;
                    }
                }
            } elseif ($fieldPim['type']=='choice') {
                $value = $this->getAttributeChoice($product, $fieldPim['field'], $fieldPim['locale']);
                if ($value) {
                    $codeMirakl = $this->getCodeMarketplace($flatProduct ['product_category'], $fieldMirakl, $value);
                    if ($codeMirakl) {
                        $flatProduct[$fieldMirakl] = $codeMirakl;
                    }
                }
            } elseif ($fieldPim['type']=='multichoice') {
                $values = $this->getAttributeMultiChoice($product, $fieldPim['field'], $fieldPim['locale']);
                if (count($values)>0) {
                    $valuesMirakls= [];
                    foreach ($values as $value) {
                        $codeMirakl = $this->getCodeMarketplace($flatProduct ['product_category'], $fieldMirakl, $value);
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
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }
}
