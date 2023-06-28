<?php

namespace App\Channels\Mirakl\Boulanger;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class BoulangerSyncProduct extends MiraklSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', ['boulanger_fr_kp'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

   
    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'REF_UNIV' => $product['identifier'],
            "TYPE_REF_UNIVERSELLE" => "SKU"
        ];

        $flatProduct["SKU_MARCHAND"] = $product['identifier'];
        $flatProduct["REF_COM"]  = substr($this->getAttributeSimple($product, 'short_article_name', 'fr_FR'), 0, 40);
        $flatProduct["ACCROCHE"] = substr($this->getAttributeSimple($product, 'article_name', 'fr_FR'), 0, 95);
        $flatProduct["PARTNUMBER"]  = $this->getAttributeSimple($product, 'ean');
        

        $descriptionRich = $this->getAttributeSimple($product, 'description_enrichie', 'fr_FR');
        $descriptionSimple = $this->getAttributeSimple($product, 'description', 'fr_FR');
        $descriptionSimple = preg_replace('/<(ul|ol|li|p|hr)[^>]*>/', '<br>', $descriptionSimple);
        $descriptionSimple = strip_tags($descriptionSimple, '<br>');

        $descriptionFinal = strlen($descriptionRich) > 5  ? $descriptionRich."<p></p>".$descriptionSimple : $descriptionSimple;
        $flatProduct['DESCRIPTIF'] = substr($descriptionFinal, 0, 5000);

        $attributeImageMain = $this->getAttributeSimple($product, 'image_url_loc_1', "fr_FR");
        $flatProduct["VISUEL_PRINC"] = $attributeImageMain ? $attributeImageMain : $this->getAttributeSimple($product, 'image_url_1');

        for ($i = 2; $i <= 9;$i++) {
            $j=$i-1;
            $attributeImageLoc = $this->getAttributeSimple($product, 'image_url_loc_'.$i, "fr_FR");
            $flatProduct["VISUEL_SEC_".$j] = $attributeImageLoc ? $attributeImageLoc : $this->getAttributeSimple($product, 'image_url_'.$i);
        }

        $flatProduct["FICHE_TECHNIQUE"]  = $this->getAttributeSimple($product, 'user_guide_url', "fr_FR");

        $flatProduct["LARGEUR_PRODUIT"] = $this->getAttributeUnit($product, 'product_lenght', 'CENTIMETER', 0);
        $flatProduct["HAUTEUR_PRODUIT"] = $this->getAttributeUnit($product, 'product_height', 'CENTIMETER', 0);
        $flatProduct["PROFONDEUR"] = $this->getAttributeUnit($product, 'product_width', 'CENTIMETER', 0);
        $flatProduct["POIDS_NET"] = $this->getAttributeUnit($product, 'product_weight', 'KILOGRAM', 0);
        
        $familyPim =$product['family'];

        if($familyPim == 'power_station') {
            $flatProduct["CATEGORIE"] = "603";
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/categorie']=  "Batterie nomade";
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/specifique_samsung']="Non";
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/specifique_apple']="Non";
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/mise_en_place']=  $familyPim == 'solar_panel' ? "Externe" : "Interne et Externe";
            $flatProduct['LISTE_CENTRALE_BATTERIE/batterie_nomade/cable_inclus']= "Aucun";
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/appareil_compatible']= "Universel";
            $flatProduct["CENTRALE_BATTERIE/caracteristiques_generales/modele_s_compatible_s"]=$this->getAttributeSimple($product, 'compatible_devices', "fr_FR");
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/courant_de_sortie_en_ma']=$this->getAttributeUnit($product, 'output_power', 'MILLIAMPERE', 0);
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/connectique']="Aucun";
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/nombre_de_port_usb']=$this->getAttributeSimple($product, 'number_usb_port');
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/puissance_de_sortie']="Non précisé";
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/capacite__en_wh']= $this->getAttributeUnit($product, 'battery_capacity_wh', 'WATTHOUR', 0);
            $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/UTILISATION']="Alimentez 99,99 % des appareils à usage intensif à la maison, à l'extérieur ou au travail.";
            $flatProduct['CENTRALE_BATTERIE/batterie_nomade/capacite__en_wh']= $this->getAttributeUnit($product, 'battery_capacity_wh', 'WATTHOUR', 0);
            $flatProduct['CENTRALE_BATTERIE/batterie_nomade/temperature_optimale_de_fonctionnement']="-10°C à 40°C";
            $flatProduct['CENTRALE_BATTERIE/batterie_nomade/nombre_de_port_usb']=$this->getAttributeSimple($product, 'number_usb_port');
            $flatProduct['CENTRALE_BATTERIE/batterie_nomade/cable_inclus']="Aucun";
        } elseif($familyPim == 'solar_panel' || $familyPim == 'fixed_solar_panel') {
            $flatProduct["CATEGORIE"] = "31809";
            $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/caracteristiques_generales/type"] = "Panneau solaire";
            $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/caracteristiques_generales/alimentation"] = "Secteur";
            $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/connectivite/technologie"] = "Wifi";
            $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/compatible_assistant_vocal/compatible_google_assistant"] = "Non";
            $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/compatible_assistant_vocal/compatible_alexa"] = "Non";
            $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/services_inclus/fabrique_en"] = "Chine";
        } elseif ($familyPim == 'robot_piscine') {
            $flatProduct["CATEGORIE"] = "7205";
            $flatProduct['CENTRALE_ROBOT_PISCINE/utilisation/type_de_piscine']="Enterrée, Hors-sol";
            $flatProduct['CENTRALE_ROBOT_PISCINE/utilisation/forme_de_piscine']="Toutes formes";
            if(in_array($product['identifier'], ['APR-ZT2001B', 'APR-ZT2001'])) {
                $flatProduct['CENTRALE_ROBOT_PISCINE/utilisation/fond_de_piscine']="Plat";
            } else {
                $flatProduct['CENTRALE_ROBOT_PISCINE/utilisation/fond_de_piscine']="Pente composée, Pente douce, Plat, Pointe diamant";
            }
            
            $flatProduct['CENTRALE_ROBOT_PISCINE/utilisation/longueur_de_piscine_en_m']=12;
            $flatProduct['CENTRALE_ROBOT_PISCINE/utilisation/revetement']="Carrelage, Liner, Coque polyester, PVC armé, Béton peint";
            $flatProduct['CENTRALE_ROBOT_PISCINE/caracteristiques_techniques/type_de_robot']="Electrique";
            $flatProduct['CENTRALE_ROBOT_PISCINE/caracteristiques_techniques/type_de_deplacement']="Intelligent";
            $flatProduct['CENTRALE_ROBOT_PISCINE/caracteristiques_techniques/type_de_nettoyage']=$this->getAttributeSimple($product, 'swim_cleaning_type', "fr_FR");
            $flatProduct['CENTRALE_ROBOT_PISCINE/caracteristiques_techniques/debit_d_aspiration_m3-h']=10;
            $flatProduct['CENTRALE_ROBOT_PISCINE/services_inclus/fabrique_en']="Chine";
        }

        $brandName = $this->getAttributeChoice($product, 'brand', "fr_FR");
        if ($brandName) {
            $codeMirakl = $this->getCodeMarketplace($flatProduct ['CATEGORIE'], "MARQUE", $brandName);
            if ($codeMirakl) {
                $flatProduct["MARQUE"] = $codeMirakl;
            }
        }

        
        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
    }
}
