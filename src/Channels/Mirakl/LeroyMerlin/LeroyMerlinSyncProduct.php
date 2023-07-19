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
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', ['leroymerlin_fr_kp'])
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
            $flatProduct['ATT_15344'] ='LOV_000002'; // not included battery
            $flatProduct["ATT_13704"] = $this->getAttributeUnit($product, 'solar_panel_power', 'WATT_CRETE', 0);

            $flatProduct['feature_00277_200259|PANNEAU_SOLAIRE|ACCESSOIRE_DE_MOTORISATION_DE_PORTAIL|R03-006-002'] ="LOV_257736"; // power station
            $flatProduct ['product_category'] =  '200259|PANNEAU_SOLAIRE|ACCESSOIRE_DE_MOTORISATION_DE_PORTAIL|R03-006-002';
            $flatProduct['feature_08547_200259|PANNEAU_SOLAIRE|ACCESSOIRE_DE_MOTORISATION_DE_PORTAIL|R03-006-002'] ='LOV_239437'; // Panneau solaire d'appoint
            $flatProduct['feature_22088_200259|PANNEAU_SOLAIRE|ACCESSOIRE_DE_MOTORISATION_DE_PORTAIL|R03-006-002'] ='LOV_070969'; // Panneau solaire d'appoint
        } elseif($familyPim == 'fixed_solar_panel') {
            $flatProduct['ATT_15344'] ='LOV_000002'; // not included battery
            $flatProduct["ATT_13704"] = $this->getAttributeUnit($product, 'solar_panel_power', 'WATT_CRETE', 0);
            $flatProduct ['product_category'] =  '200260|KIT_DE_PRODUCTION_D_ENERGIE_PHOTOVOLTAIQUE|TOITURE_SOLAIRE|R08-013-001';
            $flatProduct['feature_00277_200260|KIT_DE_PRODUCTION_D_ENERGIE_PHOTOVOLTAIQUE|TOITURE_SOLAIRE|R08-013-001'] ='LOV_210469'; // Destination
        } elseif($familyPim == 'power_station') {
            $flatProduct ['product_category'] =  "200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005";
            $flatProduct['ATT_15344'] ='LOV_000001'; // included battery
            $flatProduct['feature_08547_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005'] ='LOV_066641'; // Nom du produit : Station d'énergie
            $flatProduct['feature_00212_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005']= 'LOV_217105'; // Type de moteur à batterie
            $flatProduct['feature_11733_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005']= 'LOV_000275'; // Groupe électrogène|Type de démarrage
            $flatProduct['feature_22088_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005']= 'LOV_211666'; // Description du produit| Groupe électrogène|
            $flatProduct['ATT_20185']= 'LOV_000001'; // Régulation électronique du voltage
        } elseif($familyPim == 'robot_piscine') {
            $flatProduct ['product_category'] =  "201697|2043|R09-018-004";
            $flatProduct ['feature_08547_201697|2043|R09-018-004'] = 'LOV_207112'; // robot de piscine
        } elseif($familyPim == 'cutting_machine') {
            $flatProduct ['product_category'] =  "200595|IMPRIMANTE_3D|MACHINES_ET_MATERIEL_D_ATELIER|R04-005";
            $flatProduct ['ATT_15344'] = 'LOV_000002'; // robot de piscine
        } 


        if(array_key_exists('product_category', $flatProduct)) {
            $locales = ['fr', 'es', 'it'];

            foreach ($locales as $locale) {
                $localePim = $locale.'_'.strtoupper($locale);
                $localeMirakl = $locale.'-'.strtoupper($locale);
                $flatProduct['i18n_'.$locale.'_12963_title'] = substr($this->getAttributeSimple($product, "article_name", $localePim), 0, 150);

                $description = $this->getAttributeSimple($product, "description", $localePim);
                if($description) {
                    $descriptionFormate = str_replace('</p>', '</p><p>&nbsp;</p>', $description);
                    $descriptionFormate = str_replace(['<strong>', '</strong>'], ['<b>', '</b>'], $descriptionFormate);
                    $flatProduct['i18n_'.$locale.'_01022_longdescription'] = substr($descriptionFormate, 0, 5000);
                }

                for ($i = 1; $i <= 5;$i++) {
                    $attributeImageLoc = $this->getAttributeSimple($product, 'image_url_loc_'.$i, $localePim);
                    $keyArray = $locale == 'fr' ? 'media_'.$i : 'media_'.$i.'_'.$localeMirakl;
                    $flatProduct[$keyArray] = $attributeImageLoc ? $attributeImageLoc : $this->getAttributeSimple($product, 'image_url_'.$i);
                }

                $keyArrayMedia = $locale == 'fr' ? 'media_instruction' : 'media_instruction_'.$localeMirakl;
                $flatProduct[$keyArrayMedia]  = $this->getAttributeSimple($product, 'user_guide_url', $localePim);
        
            }

            $flatProduct["ATT_00053"] = $this->getAttributeUnit($product, 'product_lenght', 'CENTIMETER', 0);
            $flatProduct["ATT_00054"] = $this->getAttributeUnit($product, 'product_height', 'CENTIMETER', 0);
            $flatProduct["ATT_00055"] = $this->getAttributeUnit($product, 'product_width', 'CENTIMETER', 0);


     
        

            $fieldsToConvert = [
                "feature_06575_brand" => [
                    "field" => "brand",
                    "type" => "choice",
                    "locale" => "en_GB",
                ],
             ];

            foreach ($fieldsToConvert as $fieldMirakl => $fieldPim) {
                $value = null;
                if ($fieldPim['type']=='choice') {
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

        } else {
            $this->logger->info('Product not categorized');
        }


        
        
        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }
}
