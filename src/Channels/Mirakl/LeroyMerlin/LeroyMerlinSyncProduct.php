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
            $flatProduct ['product_category'] =  '200259|2230|R03-2003-2008';
            $flatProduct['feature_00277_200259|2230|R03-2003-2008'] ='LOV_032096'; // Destination
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
            $flatProduct ['feature_08547_201697|2043|R09-018-004'] = 'LOV_207112';
        } elseif($familyPim == 'cutting_machine') {
            $flatProduct ['product_category'] =  "200595|IMPRIMANTE_3D|MACHINES_ET_MATERIEL_D_ATELIER|R04-005";
            $flatProduct ['ATT_15344'] = 'LOV_000002';
        } elseif($familyPim == 'smart_home') {
            if(in_array('markerplace_blender', $product['categories'])) { // blender
                $flatProduct ['product_category'] =  "205634|1024|R1001-1002-1004"; // blender
                $flatProduct ['ATT_00056'] = $this->getAttributeUnit($product, 'liquid_capacity', 'LITER', 1); // blender
            } elseif (in_array('marketplace_air_fryer', $product['categories'])) {
                $flatProduct ['product_category'] =  "206283|2056|R1001-1002"; // friteuse
            } elseif(in_array('marketplace_computers_components_accessories', $product['categories'])) {
                $flatProduct ['product_category'] =  "200377|CPL_ET_ROUTEUR_WIFI|RESEAU_INFORMATIQUE_ET_TELEPHONIE|R03-008"; // router
            } elseif(in_array('marketplace_router_wireless', $product['categories'])) {
                $flatProduct ['product_category'] =  "200377|CPL_ET_ROUTEUR_WIFI|RESEAU_INFORMATIQUE_ET_TELEPHONIE|R03-008"; // router
            } elseif(in_array('marketplace_accessories_home', $product['categories'])) {
                $flatProduct ['product_category'] =  "200727|NIVEAU_LASER|OUTILS_DE_MESURE_ET_DE_TRACAGE|R04-003-007";
            }
        } elseif($familyPim == 'home_security') {
            $flatProduct ['product_category'] =   "201931|SERRURE_ELECTRIQUE|SERRURE_ET_CYLINDRE_DE_SERRURE|R10-007-009";
            $flatProduct ['ATT_15344'] = 'LOV_000001';
        } elseif($familyPim == 'vacuums') {
            if(!in_array('marketplace_vacuums_floorcare', $product['categories'])) {
                $flatProduct ['product_category'] = '200816|BROSSE_POUR_ASIPRATEUR|ASPIRATEUR_ET_ACCESSOIRES|R04-010-001';
            } else {
                $flatProduct ['product_category'] = '200550|2045|R04-010-001';
                $flatProduct ['ATT_15344'] = 'LOV_000001';
            }
        } elseif($familyPim == 'snow_chain') {
            $flatProduct ['product_category'] = "202727|PIECES_DETACHEES_POUR_TONDEUSE|TONDEUSE_ET_ROBOT_TONDEUSE|R09-005-004";
        } elseif($familyPim == 'car_accessories') {
            $flatProduct ['product_category'] = "205373|ENSEMBLE_DE_BATTERIE_ET_CHARGEUR|BATTERIES_ET_CHARGEURS|R04-001-018";
        } elseif($familyPim == 'telephony') {
            $flatProduct ['product_category'] = "200372|CHARGEUR_DE_TELEPHONE|CABLE_ET_CHARGEUR_DE_TELEPHONE|R03-008-005";
        } elseif($familyPim == 'smart_light') {
            $flatProduct ['product_category'] = "202358|AMPOULE_CONNECTEE|AMPOULE_CONNECTEE_ET_INTELLIGENTE|R13-003-004";
        } elseif($familyPim == 'screwdriver') {
            $flatProduct ['product_category'] = "200474|TOURNEVIS|TOURNEVIS_ET_ACCESSOIRES|R04-003-001";
        } elseif($familyPim == 'camera') {
            $flatProduct ['product_category'] = "200401|CAMERA_DE_SURVEILLANCE|VIDEOSURVEILLANCE|R03-001-003";
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


     


            $valueBrand = $this->getAttributeChoice($product, "brand", "en_GB");
            if ($valueBrand) {
                $codeMirakl = $this->getCodeMarketplaceInList('ATT_06575', $valueBrand);
                if ($codeMirakl) {
                    $flatProduct["feature_06575_brand"] = $codeMirakl;
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
