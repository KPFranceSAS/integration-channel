<?php

namespace App\Channels\FnacDarty;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;

abstract class FnacDartySyncProduct extends MiraklSyncProductParent
{
   
    
    abstract public function getChannel(): string;

    

    abstract protected function getLocalePim() : string;

   
    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'SKU_PART' => $product['identifier'],
            'EANs/EAN' => $this->getAttributeSimple($product, 'ean'),
        ];

        $flatProduct["DisplayName"] = substr((string) $this->getAttributeSimple($product, "article_name", $this->getLocalePim()), 0, 255);

        $flatProduct["Constructeur Vendeur"] = $this->getAttributeChoice($product, "brand", $this->getLocalePim());
        $descriptionFinal = $this->getAttributeSimple($product, 'description', $this->getLocalePim());
        $flatProduct['AdditionalDescription'] =$descriptionFinal ? substr((string) $descriptionFinal, 0, 4000) : null;

        $flatProduct["IMAGE|1505-1"] = $this->getAttributeSimple($product, 'image_url_1');

        for ($i = 2; $i <= 4;$i++) {
            $j=$i-1;
            $flatProduct["IMAGE|3-".$j] = $this->getAttributeSimple($product, 'image_url_'.$i);
        }

        $codeCm = $this->getCodeMarketplaceInList('lkp_Linear_Size_unit', "cm");

        $flatProduct["GRP_Height/attributeValue"] = $this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0);
        $flatProduct["GRP_Height/attributeUnit"] = $codeCm;
        $flatProduct["GRP_Width/attributeValue"] = $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0);
        $flatProduct["GRP_Width/attributeUnit"] = $codeCm;
        $flatProduct["GRP_Length/attributeValue"] = $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0);
        $flatProduct["GRP_Length/attributeUnit"] = $codeCm;
        $flatProduct["GRP_Weight/attributeValue"] =$this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 3);
        $flatProduct["GRP_Weight/attributeUnit"] = $this->getCodeMarketplaceInList('lkp_MassWeight_unit', "kg");


        for ($i = 1; $i <= 4;$i++) {
            $flatProduct["PCM_Plus_Produit_".$i] = $this->getAttributeSimple($product, 'bullet_point_'.$i, $this->getLocalePim());
        }




        $equivalences = [
           
            "marketplace_travel_oven"=>	"1700400030",
            "marketplace_pizza_peel"	=>"1100500002",
            "marketplace_pizza_cutter"=>	"1100500002",
            "marketplace_pizza_brush"	=>"1100500002",
            "marketplace_pizza_scale"=>	"1100500002",
            "marketplace_pizza_stone"	=>"1100500002",
            "marketplace_pizza_roller"=>	"1100500002",
            "marketplace_pizza_apparel"=>"1100500002",
            "marketplace_pizza_cooker"	=>"1100500002",
            "marketplace_pizza_table"	=>"2000200113",
            "marketplace_pizza_other"=>	"2000200117",
            "marketplace_video_projectors_video"=>	"401400009",
            "marketplace_projector_screen"	=>"401400008",
            "marketplace_projector_stand"	=>"401400002",
            "marketplace_projector_adapters"=>	"401400002",
            "marketplace_accessories_other"	=>"401400002",
            "marketplace_garden_spa_home"	=>"2000600112",
            "marketplace_solar_panel_energy_travel"	=>"800400201",
            "marketplace_generator_energy_travel"	=>"800400324",
            "marketplace_smart_lock"	=> "1000300012",
            "marketplace_smart_lock_accesories"=>	"1000300012",
            'marketplace_solar_panel_mobile' =>	"800400201",
            'marketplace_camera_waterproof_accessories'  => "400300015",
           'marketplace_camera_battery' => "400300015",
           'marketplace_camera_selfie'  => "400300015",
           'marketplace_camera_charger' => "400300015",
           'marketplace_camera_stands'  => "400300001",
           'marketplace_camera_light'   => "400300015",
           'marketplace_camera_tripod'  => "400300001",
           'marketplace_camera_accessories' => "400300015",
           'marketplace_camera_video' =>   "400800004",
            'marketplace_hair_care_health_personal_care' =>	"1700200006",
            'marketplace_turntable' =>		'402300004'	,
            'marketplace_video_3D'	 =>	'401300009'	 ,
            'marketplace_accessories_other'	 =>	 '401400002'
        ];



        

        foreach($equivalences as $pimCategory => $mmCategory) {
            if(in_array($pimCategory, $product['categories'])) {
                $flatProduct['Typology'] = $mmCategory;
                break;
            }
        }

       


        if(array_key_exists('Typology', $flatProduct)) {
            
        } else {
            $this->logger->info('Product not categorized');
        }

        return $flatProduct;
    }








}
