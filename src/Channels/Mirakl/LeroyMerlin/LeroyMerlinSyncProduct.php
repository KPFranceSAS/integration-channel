<?php

namespace App\Channels\Mirakl\LeroyMerlin;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class LeroyMerlinSyncProduct extends MiraklSyncProductParent
{

    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'shop_sku' => $product['identifier'],
            'gtin_EAN13' => $this->getAttributeSimple($product, 'ean'),
        ];


        $flatProduct["ATT_00053"] = $this->getAttributeUnit($product, 'product_lenght', 'CENTIMETER', 0);
        $flatProduct["ATT_00054"] = $this->getAttributeUnit($product, 'product_height', 'CENTIMETER', 0);
        $flatProduct["ATT_00055"] = $this->getAttributeUnit($product, 'product_width', 'CENTIMETER', 0);

        $flatProduct["feature_06575_brand"] = $this->getCodeMarketplaceInList('ATT_06575', $this->getAttributeChoice($product, "brand", "en_GB"));

        
       $flatProduct['product_category'] = $this->getCategoryNode($this->getAttributeSimple($product, 'mkp_product_type'), 'leroymerlin');
       


        if(array_key_exists('product_category', $flatProduct)) {
            switch($flatProduct['product_category']) {

                case "206556|2547|R1001-2010" : // prineter
                    $flatProduct['feature_22088_206556|2547|R1001-2010'] ="LOV_283859"; // LOV_283859
                    break;
                case "202599|2480|R15-2012"  : // desk
                    $flatProduct ['ATT_21148'] =  'LOV_000001'; // Contain woods ;
                    break;
                case '200264|2231|R03-2003-2008':
                    $flatProduct["ATT_13704"] = $this->getAttributeUnit($product, 'solar_panel_power', 'WATT_CRETE', 0);
                    $flatProduct['feature_00277_200259|PANNEAU_SOLAIRE|ACCESSOIRE_DE_MOTORISATION_DE_PORTAIL|R03-006-002'] ="LOV_257736"; // power station
                    $flatProduct['feature_08547_200259|PANNEAU_SOLAIRE|ACCESSOIRE_DE_MOTORISATION_DE_PORTAIL|R03-006-002'] ='LOV_239437'; // Panneau solaire d'appoint
                    $flatProduct['feature_22088_200259|PANNEAU_SOLAIRE|ACCESSOIRE_DE_MOTORISATION_DE_PORTAIL|R03-006-002'] ='LOV_070969'; // Panneau solaire d'appoint
                    break;
                case '200260|2228|R03-2003-2007':
                    $flatProduct["ATT_13704"] = $this->getAttributeUnit($product, 'solar_panel_power', 'WATT_CRETE', 0);
                    $flatProduct['feature_00277_200260|2228|R03-2003-2007'] ='LOV_000653'; // Destination
                    break;
                case  "200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005":
                    $flatProduct['feature_08547_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005'] ='LOV_066641'; // Nom du produit : Station d'énergie
                    $flatProduct['feature_00212_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005']= 'LOV_217105'; // Type de moteur à batterie
                    $flatProduct['feature_11733_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005']= 'LOV_000275'; // Groupe électrogène|Type de démarrage
                    $flatProduct['feature_22088_200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005']= 'LOV_211666'; // Description du produit| Groupe électrogène|
                    $flatProduct['ATT_20185']= 'LOV_000001'; // Régulation électronique du voltage
                    break;
                case  "201697|2043|R09-018-004": // robot
                    $flatProduct ['feature_08547_201697|2043|R09-018-004'] = 'LOV_207112';
                    break;
                case  "205634|1024|R1001-1002-1004": // blender
                    $flatProduct ['ATT_00056'] = $this->getAttributeUnit($product, 'liquid_capacity', 'LITER', 1); // blender ;
                    break;
                case  "201508|FOUR_A_PIZZA|BARBECUE_PLANCHA_ET_CUISINE_D_EXTERIEUR|R09-007": // pizza
                    $flatProduct ['ATT_20510'] =  'LOV_000001'; // Food contact ;
                    break;
                case  "201516|ACCESSOIRE_POUR_CUISINER|BARBECUE|R09-007-003": // accessoires
                    $flatProduct ['ATT_20510'] =  'LOV_000001'; // Food contact ;
                    break;
                case "201526|ROBOT_TONDEUSE|TONDEUSE_ET_ROBOT_TONDEUSE|R09-005-004": //lawn motor
                    $flatProduct ['ATT_15344'] =  'LOV_000001'; // Lawn motor ;
                    break;

                    
                
            };

            if(in_array($flatProduct['product_category'], [
                '201825|ROSACE_DE_FONCTION|POIGNEE_DE_PORTE|R10-007-004',
                "200474|TOURNEVIS|TOURNEVIS_ET_ACCESSOIRES|R04-003-001",
                "201675|2538|R05-007",
                "205634|1024|R1001-1002-1004",
                '201508|FOUR_A_PIZZA|BARBECUE_PLANCHA_ET_CUISINE_D_EXTERIEUR|R09-007'
                ])) {
                $flatProduct['ATT_21148'] ='LOV_000002';// Contain woods ;
            } 



            if(in_array($flatProduct['product_category'], [
                    '200589|GROUPE_ELECTROGENE|MACHINES_ET_MATERIEL_D_ATELIER|R04-005',
                    "201931|SERRURE_ELECTRIQUE|SERRURE_ET_CYLINDRE_DE_SERRURE|R10-007-009",
                    "200550|2045|R04-010-001"
                    ])) {
                $flatProduct['ATT_15344'] ='LOV_000001'; // included battery
            } else {
                $flatProduct ['ATT_15344'] = 'LOV_000002';
            }
        } else {
            $this->logger->info('Product not categorized');
        }
        

        $locales = ['fr', 'es', 'it'];

        foreach ($locales as $locale) {
            $localePim = $locale.'_'.strtoupper($locale);
            $localeMirakl = $locale.'-'.strtoupper($locale);
            $flatProduct['i18n_'.$locale.'_12963_title'] = substr((string) $this->getAttributeSimple($product, "article_name", $localePim), 0, 150);

            $description = $this->getAttributeSimple($product, "description", $localePim);
            if($description) {
                $descriptionFormate = str_replace('</p>', '</p><p>&nbsp;</p>', (string) $description);
                $descriptionFormate = str_replace(['<strong>', '</strong>'], ['<b>', '</b>'], $descriptionFormate);
                $flatProduct['i18n_'.$locale.'_01022_longdescription'] = substr($descriptionFormate, 0, 5000);
            }

            for ($i = 1; $i <= 5;$i++) {
                $attributeImageLoc = $this->getAttributeSimple($product, 'image_url_loc_'.$i, $localePim);
                $keyArray = $locale == 'fr' ? 'media_'.$i : 'media_'.$i.'_'.$localeMirakl;
                $flatProduct[$keyArray] = $attributeImageLoc ?: $this->getAttributeSimple($product, 'image_url_'.$i);
            }

            $keyArrayMedia = $locale == 'fr' ? 'media_instruction' : 'media_instruction_'.$localeMirakl;
            $flatProduct[$keyArrayMedia]  = $this->getAttributeSimple($product, 'user_guide_url', $localePim);
        
        }

        
        return $flatProduct;
    }







    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_LEROYMERLIN;
    }
}
