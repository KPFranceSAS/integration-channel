<?php

namespace App\Channels\Mirakl\MediaMarkt;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class MediaMarktSyncProduct extends MiraklSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', ['mediamarkt_es_gi'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

   
    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'SHOP_SKU' => $product['identifier'],
            'EAN' => $this->getAttributeSimple($product, 'ean'),
        ];

        $flatProduct["BRAND"] = $this->getCodeMarketplaceInList('LOV_MP_BRAND_NAME', $this->getAttributeChoice($product, "brand", "en_GB"));



        $equivalences = [
            'marketplace_soundbar'=>'FET_FRA_1060',
            'marketplace_homespeaker'=>'FET_FRA_1047',
            'marketplace_portablespeaker'=>'FET_FRA_1046',
            'marketplace_receiver'=>'FET_FRA_1043',
            'marketplace_video_projectors_video'=>'FET_FRA_1105',
            'marketplace_accessories_other'=>'FET_FRA_1143',
            'marketplace_projector_stand'=>'FET_FRA_1639',
            'marketplace_projector_adapters'=>'FET_FRA_1640',
            'marketplace_smart_lock'=>'FET_FRA_1175',
            'marketplace_hair_care_health_personal_care'=>'FET_FRA_1030',
            'marketplace_cutting_machines_art_crafts'=>'FET_FRA_2023',
            'marketplace_accessories_audio' => 'FET_FRA_1224',
            'marketplace_accessories_video' => 'FET_FRA_1143'
        ];

        foreach($equivalences as $pimCategory => $mmCategory) {
            if(in_array($pimCategory, $product['categories'])) {
                $flatProduct['CATEGORY'] = $mmCategory;
                $flatProduct['PROD_FEAT_10990__ES_ES'] = $this->getCategorieName($pimCategory, 'es_ES');
                break;
            }
        }



        
        // text
        $short_title = $this->getAttributeSimple($product, "short_article_name", 'es_ES');
        $flatProduct['TITLE__ES_ES'] = $short_title ? $short_title  : substr($this->getAttributeSimple($product, "article_name", 'es_ES'), 0, 100);
        $flatProduct['Product_Description__ES_ES'] = $this->getAttributeSimple($product, "description", 'es_ES');

        // Medias
        $flatProduct["ATTR_PROD_MP_MainProductImage"] =  $this->getAttributeSimple($product, 'image_url_1');
        $flatProduct["ATTR_PROD_MP_AdditionalImage1"] =  $this->getAttributeSimple($product, 'image_url_2');
        $flatProduct["ATTR_PROD_MP_AdditionalImage2"] =  $this->getAttributeSimple($product, 'image_url_3');
        $flatProduct["ATTR_PROD_MP_DetailView1"] =  $this->getAttributeSimple($product, 'image_url_4');
        $flatProduct["ATTR_PROD_MP_DetailView2"] =  $this->getAttributeSimple($product, 'image_url_5');
        $flatProduct["ATTR_PROD_MP_DetailView3"] =  $this->getAttributeSimple($product, 'image_url_6');
        $flatProduct["ATTR_PROD_MP_LifeStyleImage1"] =  $this->getAttributeSimple($product, 'image_url_7');
        $flatProduct["ATTR_PROD_MP_LifeStyleImage2"] =  $this->getAttributeSimple($product, 'image_url_8');
        $flatProduct["ATTR_PROD_MP_LifeStyleImage3"] =  $this->getAttributeSimple($product, 'image_url_9');
        
        // Dimensions
        $flatProduct["PROD_FEAT_16110"] = $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_16111"] = $this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_16112"] = $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_16333"] = $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 3).' kg';

        // attribtues
        $flatProduct["PROD_FEAT_10134__ES_ES"] = implode(", ", $this->getAttributeMultiChoice($product, 'connectivity_technology', 'es_ES'));
        $colorName = $this->getAttributeChoice($product, "color", "es_ES");
        $flatProduct["PROD_FEAT_10812__ES_ES"] = $colorName ? $colorName : $this->getAttributeChoice($product, "color_generic", "es_ES");
        $flatProduct["PROD_FEAT_00003"] = $this->getCodeMarketplaceInList('LOV_FEAT_Color_basic', $this->getAttributeChoice($product, "color_generic", "en_GB"));
        $contentBox= $this->getAttributeSimple($product, "in_the_box", "es_ES");
        $flatProduct["PROD_FEAT_11470__ES_ES"] =  $contentBox ? strip_tags(str_replace('</li>', ', </li>', $contentBox)) :'';

        // audio
        $flatProduct["PROD_FEAT_11437__ES_ES"] = implode(", ", $this->getAttributeMultiChoice($product, 'speaker_type', 'es_ES'));
        $flatProduct["PROD_FEAT_10226__ES_ES"] = $this->getAttributeChoice($product, 'speaker_number', 'es_ES');
        $flatProduct["PROD_FEAT_10026"] = $this->getCodeMarketplaceInList('LOV_FEAT_Number_of_channels', $this->getAttributeChoice($product, "speaker_channels", "en_GB"));
        $powerSource = $this->getAttributeUnit($product, 'nominal_power', 'WATT', 3);
        $flatProduct["PROD_FEAT_15961"] = $powerSource ? $powerSource.' W' : null;
        
        $powerSourceType = $this->getAttributeSimple($product, 'power_source_type');
        $equiPower =[
            "ac" =>	"60",
            "ac_and_battery" =>	"40",
            "battery"=>	"20",
        ];
        if($powerSourceType && array_key_exists($powerSourceType, $equiPower)) {
            $flatProduct["PROD_FEAT_16630"]  = $equiPower[$powerSourceType];
        }
        
        
        // videoprojecteur
        $flatProduct["PROD_FEAT_14702"] = $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_14701"] = $this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_14700"] = $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_14704"] = $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 3).' kg';


        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }
}
