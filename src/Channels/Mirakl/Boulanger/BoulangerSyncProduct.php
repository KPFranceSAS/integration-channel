<?php

namespace App\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class BoulangerSyncProduct extends MiraklSyncProductParent
{
  

   
    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'REF_UNIV' => $product['identifier'],
            "TYPE_REF_UNIVERSELLE" => "SKU"
        ];

        $flatProduct["SKU_MARCHAND"] = $product['identifier'];
        $flatProduct["REF_COM"]  = substr((string) $this->getAttributeSimple($product, 'short_article_name', 'fr_FR'), 0, 40);
        $flatProduct["ACCROCHE"] = substr((string) $this->getAttributeSimple($product, 'article_name', 'fr_FR'), 0, 95);
        $flatProduct["PARTNUMBER"]  = $this->getAttributeSimple($product, 'ean');
        
        $flatProduct["MARQUE"] = $this->getCodeMarketplaceInList('LISTE_MARQUE', $this->getAttributeChoice($product, "brand", "en_GB"));


        $descriptionRich = $this->getAttributeSimple($product, 'description_enrichie', 'fr_FR');
        $descriptionSimple = $this->getAttributeSimple($product, 'description', 'fr_FR');
        $descriptionSimple = preg_replace('/<(ul|ol|li|p|hr)[^>]*>/', '<br>', (string) $descriptionSimple);
        $descriptionSimple = strip_tags($descriptionSimple, '<br>');

        $descriptionFinal = strlen((string) $descriptionRich) > 5  ? $descriptionRich."<p></p>".$descriptionSimple : $descriptionSimple;
        $flatProduct['DESCRIPTIF'] = substr($descriptionFinal, 0, 5000);

        $attributeImageMain = $this->getAttributeSimple($product, 'image_url_loc_1', "fr_FR");
        $flatProduct["VISUEL_PRINC"] = $attributeImageMain ?: $this->getAttributeSimple($product, 'image_url_1');

        for ($i = 2; $i <= 9;$i++) {
            $j=$i-1;
            $attributeImageLoc = $this->getAttributeSimple($product, 'image_url_loc_'.$i, "fr_FR");
            $flatProduct["VISUEL_SEC_".$j] = $attributeImageLoc ?: $this->getAttributeSimple($product, 'image_url_'.$i);
        }

        $flatProduct["FICHE_TECHNIQUE"]  = $this->getAttributeSimple($product, 'user_guide_url', "fr_FR");

        $flatProduct["LARGEUR_PRODUIT"] = $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0);
        $flatProduct["HAUTEUR_PRODUIT"] = $this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0);
        $flatProduct["PROFONDEUR"] = $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0);
        $flatProduct["POIDS_NET"] = $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 0);
        

        $flatProduct['CATEGORY'] = $this->getCategoryNode($this->getAttributeSimple($product, 'mkp_product_type'), 'boulanger');

        if(array_key_exists('CATEGORIE', $flatProduct)) {
            switch($flatProduct['CATEGORIE']) {
                case '603':
                    $flatProduct = $this->addInfoPowerStation($product, $flatProduct);
                    break;
                case '31809':
                    $flatProduct = $this->addInfoSolarPanel($product, $flatProduct);
                    break;
                case '7205':
                    $flatProduct = $this->addInfoPoolRobot($product, $flatProduct);
                    break;
                case '2402':
                    $flatProduct = $this->addInfoHomeSecurity($product, $flatProduct);
                    break;
                case '5603':
                    $flatProduct = $this->addInfoBlender($product, $flatProduct);
                    break;
                case '8004':
                    $flatProduct = $this->addInfoFryer($product, $flatProduct);

                    break;
                case '30602':
                    $flatProduct = $this->addInfoPizza($product, $flatProduct);
    
                    break;
                case '7201':
                    $flatProduct = $this->addInfoRobotTondeuse($product, $flatProduct);
    
                 break;
                    case '8011':
                        $flatProduct = $this->addInfoComposteur($product, $flatProduct);
        
                        break;

                    
                case "6001":
                    $flatProduct = $this->addInfoAccesoriesPizza($product, $flatProduct);
    
                    break;



            };
            

           
        } else {
            $this->logger->info('Product not categorized');
        }


        return $flatProduct;
    }


    public function addInfoRobotTondeuse(array $product, array $flatProduct): array
    {

            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/produit']='Tondeuse robot';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/alimentation']='Batterie';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/type_de_chargement']='Base de recharge (automatique)';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/autonomie_en_heure']='1,0 h';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/temps_de_charge_en_heure']='1,0 h';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/surface_couverte_m2']='500 m²';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/niveau_sonore']='58 dB';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_generale/coloris']='Gris';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_specifique/procede']='Robot tondeuse connecté sans fil périphérique';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/caracteristique_specifique/hauteurs_de_coupe' ]='2 à 7,6 cm';
            $flatProduct['CENTRALE_TONDEUSE_GAZON/services_inclus/fabrique_en'] = 'Chine';
        
        return $flatProduct;
    }




    public function addInfoComposteur(array $product, array $flatProduct): array
    {

            $flatProduct['CENTRALE_POUBELLE/composition/type_de_produit']='Composteur de cuisine'; 
            $flatProduct['CENTRALE_POUBELLE/composition/mecanisme_de_la_poubelle']='Automatique'; 
            $flatProduct['CENTRALE_POUBELLE/composition/ouverture_pedale']='Non'; 
            $flatProduct['CENTRALE_POUBELLE/composition/matiere_du_corps']='Acier inoxydable'; 
            $flatProduct['CENTRALE_POUBELLE/composition/matiere_du_couvercle']='Polypropylène'; 
            $flatProduct['CENTRALE_POUBELLE/composition/nombre_de_bac']='1'; 
            $flatProduct['CENTRALE_POUBELLE/composition/capacite_de_chaque_bac']='30'; 
            $flatProduct['CENTRALE_POUBELLE/composition/coloris']=$this->getAttributeChoice($product, "color", "fr_FR"); 
            $flatProduct['CENTRALE_POUBELLE/dimensions/hauteur_cm']=$this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0);
            $flatProduct['CENTRALE_POUBELLE/dimensions/largeur_cm']= $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0); 
            $flatProduct['CENTRALE_POUBELLE/dimensions/profondeur_cm']=$this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0); 
            $flatProduct['CENTRALE_POUBELLE/services_inclus/fabrique_en']='Chine'; 
            $flatProduct['CENTRALE_POUBELLE/dimensions/capacite_l']='30'; 
        
        return $flatProduct;
    }
    

    public function addInfoAccesoriesPizza(array $product, array $flatProduct): array
    {
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/descriptif_de_l_accessoire/type_de_produit'] = $this->getAttributeSimple($product, 'mkp_product_type', 'fr_FR');
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/descriptif_de_l_accessoire/compatible_avec' ] ='Four à Pizza Witt';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/descriptif_de_l_accessoire/collection_accessoires'] ='Four à Pizza Witt';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/descriptif_de_l_accessoire/usage'] ='Cuisson pizza';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/descriptif_de_l_accessoire/matiere_de_l_accessoire' ] ='';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/compatibilite_de_l_accessoire/modele_1'] ='';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/conseil_de_securite' ] ='Produit non concerné';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/visuel_packaging_integrale_obligatoire_pour_les_produits_dangereux'] ='Non concerné';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_cancerogene'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_comburant_facilite_la_combustion'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_corrosif'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_dangereux_en_milieu_aquatique'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_explosif'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_avec_gaz_sous_pression'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_inflammable'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_toxique'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/precautions_d_utilisation_du_produit/produit_toxique_et_irritant'] ='Non';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/contenu_du_carton/notice']= 'Oui';
        $flatProduct['CENTRALE_ACCESSOIRE_BARBECUE/services_inclus/fabrique_en']='Chine';

        return $flatProduct;
    }



    public function addInfoPizza(array $product, array $flatProduct): array
    {

        $flatProduct['CENTRALE_PIZZA_GRILL/contenu_du_carton/notice']= 'Oui';
        $flatProduct['CENTRALE_PIZZA_GRILL/cuisson/nombre_de_personnes']='De 6 à 8 personnes';
        $flatProduct['CENTRALE_PIZZA_GRILL/cuisson/diametre_de-des_pizza_s']='40 cm';
        $flatProduct['CENTRALE_PIZZA_GRILL/cuisson/energie']='Gaz';
        $flatProduct['CENTRALE_PIZZA_GRILL/cuisson/thermostat_reglable']='Oui';
        $flatProduct['CENTRALE_PIZZA_GRILL/cuisson/temperature_maximum_de_cuisson']='500°C';
        $flatProduct['CENTRALE_PIZZA_GRILL/cuisson/rapidite_de_montee_en_temperature']='15 minutes';
      
        $flatProduct['CENTRALE_PIZZA_GRILL/cuisson/temps_de_cuisson']='1 min';

        $flatProduct['CENTRALE_PIZZA_GRILL/equipement/nombre_de_plaques']='1 pierre';
        $flatProduct['CENTRALE_PIZZA_GRILL/equipement/thermometre']='Non';
        $flatProduct['CENTRALE_PIZZA_GRILL/equipement/minuteur']='Non';
        $flatProduct['CENTRALE_PIZZA_GRILL/equipement/signal_sonore_de_fin_de_cuisson']='Non';
      
        $flatProduct['CENTRALE_PIZZA_GRILL/matiere_et_coloris/de_la_plaque']='Pierre réfractaire';
        $flatProduct['CENTRALE_PIZZA_GRILL/matiere_et_coloris/de_la_coque']='Acier inoxydable';
        $flatProduct['CENTRALE_PIZZA_GRILL/matiere_et_coloris/coloris']='Noir';
        $flatProduct['CENTRALE_PIZZA_GRILL/matiere_et_coloris/matiere_de_la_cavite']='Inox';
        
      
        $flatProduct['CENTRALE_PIZZA_GRILL/facilite_de_nettoyage/plaque_amovible']='Oui';
        $flatProduct['CENTRALE_PIZZA_GRILL/agencement/mobilite']='Portable';
        $flatProduct['CENTRALE_PIZZA_GRILL/agencement/poignees_de_transport']='Non';
        $flatProduct['CENTRALE_PIZZA_GRILL/securite/poignee_froide']='Non';
        $flatProduct['CENTRALE_PIZZA_GRILL/securite/parois_froides']='Non';
        $flatProduct['CENTRALE_PIZZA_GRILL/services_inclus/fabrique_en']='Chine';
        $flatProduct['CENTRALE_PIZZA_GRILL/caracteristiques_generales/fonction']='Four à pizza';

        $flatProduct['CENTRALE_PIZZA_GRILL/dimensions/hauteur_produit']=$this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0);
        $flatProduct['CENTRALE_PIZZA_GRILL/dimensions/profondeur_produit']= $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0);
        $flatProduct['CENTRALE_PIZZA_GRILL/dimensions/poids_net']= $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 0);
        $flatProduct['CENTRALE_PIZZA_GRILL/dimensions/largeur_produit']=$this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0);
     
        return $flatProduct;
    }



    public function addInfoHomeSecurity(array $product, array $flatProduct): array
    {
        if(in_array('marketplace_smart_lock', $product['categories'])) {
            $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/type_de_produit"] = "Serrure";
            $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/usage"] = "Déverrouiller ou verrouiller votre porte avec Smartphone";
        } elseif (in_array('marketplace_smart_lock_accesories', $product['categories'])) {
            $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/type_de_produit"] = "Accessoire pour serrure connectée";
            $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/usage"] = "Déverrouiller ou verrouiller votre porte sans Smartphone";
        }
        $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/mode_d_installation"] = "Non concerné";
        $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/installation"] = "Le Smart Lock Cylinder de Bold est conçu pour une installation facile, s'intégrant parfaitement à votre système de verrouillage existant.";
        $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/coloris"] = "Gris";
        $flatProduct["CENTRALE_SECURITE_MAISON/compatibilite/wifi"] = "Oui";
        $flatProduct["CENTRALE_SECURITE_MAISON/compatibilite/bluetooth"] = "Oui";
        $flatProduct["CENTRALE_SECURITE_MAISON/services_inclus/fabrique_en"] = "Chine";
        $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/nombre_de_camera"] = "Non concerné";
        $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/technologie_utilisee"] = "Bluetooth et Wi-Fi ";
        $flatProduct["CENTRALE_SECURITE_MAISON/caracteristiques_generales/ecran"] = "Non";
        $flatProduct["CENTRALE_SECURITE_MAISON/alimentation/fonctionne"] = "Sur batterie";
        $flatProduct["CENTRALE_SECURITE_MAISON/alimentation/autonomie"] = " Jusqu'à 2 ans";
        $flatProduct["CENTRALE_SECURITE_MAISON/compatibilite/protocole"] = "Non";
        $flatProduct["CENTRALE_SECURITE_MAISON/assistant_vocal_integre/assistant_vocal"] = "Non concernée";
        $flatProduct["CENTRALE_SECURITE_MAISON/assistant_vocal_integre/google_assistant"] = "Non";
        $flatProduct["CENTRALE_SECURITE_MAISON/assistant_vocal_integre/alexa"] = "Non";
        $flatProduct["CENTRALE_SECURITE_MAISON/assistant_vocal_integre/siri"] = "Non";
        $flatProduct["CENTRALE_SECURITE_MAISON/dimensions/largeur_produit"] = $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0);
        $flatProduct["CENTRALE_SECURITE_MAISON/dimensions/hauteur_produit"] = $this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0);
        $flatProduct["CENTRALE_SECURITE_MAISON/dimensions/profondeur_produit"] = $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0);
        $flatProduct["CENTRALE_SECURITE_MAISON/dimensions/poids_net"] = $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 0);
        $flatProduct["CENTRALE_SECURITE_MAISON/dimensions/poids_brut"] = $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 0);

        return $flatProduct;
    }


    public function addInfoPowerStation(array $product, array $flatProduct): array
    {
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
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/caracteristiques_generales/type"] = "Panneau solaire";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/caracteristiques_generales/coloris"] = "Noir";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/caracteristiques_generales/alimentation"] = "Secteur";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/connectivite/technologie"] = "Wifi";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/compatible_assistant_vocal/compatible_google_assistant"] = "Non";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/compatible_assistant_vocal/compatible_alexa"] = "Non";
        $flatProduct["CENTRALE_CHAUFFAGE_CONNECTE/services_inclus/fabrique_en"] = "Chine";
        
        return $flatProduct;
    }

    public function addInfoPoolRobot(array $product, array $flatProduct): array
    {
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
        $flatProduct['CENTRALE_BLENDER/facettes_blender/coloris']='Blanc';

        return $flatProduct;
    }

    public function addInfoFryer(array $product, array $flatProduct): array
    {
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
        $flatProduct['CENTRALE_FRITEUSE/volume_et_capacite/capacite_de_frites_fraiches']=0.8;
        $flatProduct['CENTRALE_FRITEUSE/volume_et_capacite/capacite_de_frites_surgelees']=0.8;
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
        $flatProduct['CENTRALE_FRITEUSE/services_inclus/coloris']='Blanc';
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
