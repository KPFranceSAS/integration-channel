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
            $flatProduct = $this->addInfoPowerStation($product, $flatProduct);
        } elseif($familyPim == 'solar_panel' || $familyPim == 'fixed_solar_panel') {
            $flatProduct = $this->addInfoSolarPanel($product, $flatProduct);
        } elseif ($familyPim == 'robot_piscine') {
            $flatProduct = $this->addInfoPoolRobot($product, $flatProduct);
        } elseif($familyPim == 'smart_home') {
            if(in_array('markerplace_blender', $product['categories'])) { // blender
                $flatProduct = $this->addInfoBlender($product, $flatProduct);
            } elseif (in_array('marketplace_air_fryer', $product['categories'])) {
                $flatProduct = $this->addInfoFryer($product, $flatProduct);
            }
        }


        if(array_key_exists('CATEGORIE', $flatProduct)) {
            $brandName = $this->getAttributeChoice($product, 'brand', "fr_FR");
            if ($brandName) {
                $codeMirakl = $this->getCodeMarketplace($flatProduct ['CATEGORIE'], "MARQUE", $brandName);
                if ($codeMirakl) {
                    $flatProduct["MARQUE"] = $codeMirakl;
                }
            }
        } else {
            $this->logger->info('Product not categorized');
        }

        return $flatProduct;
    }



    public function addInfoPowerStation(array $product, array $flatProduct): array
    {
        $flatProduct["CATEGORIE"] = "603";
        $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/categorie']=  "Batterie nomade";
        $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/specifique_samsung']="Non";
        $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/specifique_apple']="Non";
        $flatProduct['CENTRALE_BATTERIE/caracteristiques_generales/mise_en_place']=  $product['family'] == 'solar_panel' ? "Externe" : "Interne et Externe";
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
        return $flatProduct;
    }


    public function addInfoSolarPanel(array $product, array $flatProduct): array
    {
        $flatProduct["CATEGORIE"] = "31809";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/caracteristiques_generales/type"] = "Panneau solaire";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/caracteristiques_generales/alimentation"] = "Secteur";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/connectivite/technologie"] = "Wifi";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/compatible_assistant_vocal/compatible_google_assistant"] = "Non";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/compatible_assistant_vocal/compatible_alexa"] = "Non";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/services_inclus/fabrique_en"] = "Chine";
    
        return $flatProduct;
    }

    public function addInfoPoolRobot(array $product, array $flatProduct): array
    {
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
        return $flatProduct;
    }


    public function addInfoBlender(array $product, array $flatProduct): array
    {
        $flatProduct['CATEGORIE'] = "5603"; // blender
        $flatProduct['CENTRALE_BLENDER/contenu_du_carton/notice']='Oui';
        $flatProduct['CENTRALE_BLENDER/performances_du_blender/blender_professionnel']='Non';
        $flatProduct['CENTRALE_BLENDER/performances_du_blender/puissance_moteur__en_watts']=1000;
        $flatProduct['CENTRALE_BLENDER/performances_du_blender/nombre_de_tours_minutes_maxi']=20000;
        $flatProduct['CENTRALE_BLENDER/performances_du_blender/vitesse_automatique']='Non';
        $flatProduct['CENTRALE_BLENDER/performances_du_blender/fonction_pulse']='Oui';
        $flatProduct['CENTRALE_BLENDER/performances_du_blender/matiere_du_corps']='Plastique';
        $flatProduct['CENTRALE_BLENDER/performances_du_blender/couleur']='Blanc';
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/capacite_totale_du_bol']=2;
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/capacite_de_preparation']=1.5;
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/matiere_du_bol']='Verre thermoresist';
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/bol_avec_poignee']='Oui';
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/robinet_verseur']="Oui, permet un débit à l'infini";
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/bol_gradue']='Oui';
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/poids_du_bol']=2;
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/nombre_de_lames']=8;
        $flatProduct['CENTRALE_BLENDER/bol_et_lames/matiere_des_lames']='Acier inoxydable';
        $flatProduct['CENTRALE_BLENDER/confort_d_utilisation/sans_fil']='Non';
        $flatProduct['CENTRALE_BLENDER/confort_d_utilisation/longueur_du_cordon__en_metres']=1;
        $flatProduct['CENTRALE_BLENDER/confort_d_utilisation/haute_vitesse']='Oui';
        $flatProduct['CENTRALE_BLENDER/nettoyage_et_securite/fonction_nettoyante']='Oui';
        $flatProduct['CENTRALE_BLENDER/nettoyage_et_securite/elements_compatibles_lave-vaisselle']='Oui';
        $flatProduct['CENTRALE_BLENDER/nettoyage_et_securite/lames_amovibles']='Oui';
        $flatProduct['CENTRALE_BLENDER/nettoyage_et_securite/systeme_de_verrouillage']='Le blender démarre uniquement quand le bol est verrouillé';
        $flatProduct['CENTRALE_BLENDER/nettoyage_et_securite/sans_bisphenol_a']='Oui';
        $flatProduct['CENTRALE_BLENDER/caracteristiques_generales/type_produit']='Blender chauffant';
        $flatProduct['CENTRALE_BLENDER/services_inclus/fabrique_en']='Chine';
        $flatProduct['CENTRALE_BLENDER/caracteristiques_generales/couleur']='Blanc';

        return $flatProduct;
    }

    public function addInfoFryer(array $product, array $flatProduct): array
    {
        $flatProduct['CATEGORIE'] =  "8004"; // friteuse
        $flatProduct['CENTRALE_FRITEUSE/contenu_du_carton/notice']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/caracteristiques_generales/type_de_friteuse']='Friteuse connectée';
        $flatProduct['CENTRALE_FRITEUSE/caracteristiques_generales/coloris_friteuse']='Blanc';
        $flatProduct['CENTRALE_FRITEUSE/choisir_ce_produit_pour_quels_usages/cuisson']="avec une cuillère d'huile ou sans huile";
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/preparer_les_frites']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/snaking']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/beignets']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/gateaux']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/poulet_roti']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/surgeles']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/nuggets']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/viande_grillee']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/a_quoi_ca_sert/churros']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/frire']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/rotir']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/griller']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/rechauffer']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/cuire']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/cuire_comme_dans_un_four']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/deshydrater']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/fonctions/maintenir_au_chaud']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/volume_et_capacite/capacite_de_frites_fraiches']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/volume_et_capacite/capacite_de_frites_surgelees']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/volume_et_capacite/type_de_friture']='air chaud';
        $flatProduct['CENTRALE_FRITEUSE/volume_et_capacite/nombre_de_bacs_et_paniers']='1 bac';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/puissance']=1600;
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/chaleur_pulsee']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/voyant_de_temperature']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/thermostat_reglable']= "jusqu'à 200°C";
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/minuterie']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/mode_de_cuisson']='automatique et manuel';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/ecran']='digital';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/depart_differe']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/fonction_pause']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/confort_d_utilisation/arret_automatique']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/entretien/resistance']='cachée';
        $flatProduct['CENTRALE_FRITEUSE/entretien/cuve_amovible']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/entretien/zone_froide']='Oui';
        $flatProduct['CENTRALE_FRITEUSE/entretien/filtres']='métallique et charbon';
        $flatProduct['CENTRALE_FRITEUSE/entretien/filtration_de_l_huile']='nettoyage facile';
        $flatProduct['CENTRALE_FRITEUSE/services_inclus/fabrique_en']='Chine';
        $flatProduct['CENTRALE_FRITEUSE/dimensions/largeur_produit']=25;
        $flatProduct['CENTRALE_FRITEUSE/dimensions/hauteur_produit']=33;
        $flatProduct['CENTRALE_FRITEUSE/dimensions/profondeur_produit']=30;
        $flatProduct['CENTRALE_FRITEUSE/dimensions/poids_net']=4;

        return $flatProduct;
    }




   

    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_BOULANGER;
    }
}
