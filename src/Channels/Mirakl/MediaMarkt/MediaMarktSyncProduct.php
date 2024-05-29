<?php

namespace App\Channels\Mirakl\MediaMarkt;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class MediaMarktSyncProduct extends MiraklSyncProductParent
{
      

   
    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);

        $flatProduct = [
            'SHOP_SKU' => $product['identifier'],
            'EAN' => $this->getAttributeSimple($product, 'ean'),
        ];

        $flatProduct["BRAND"] = $this->getCodeMarketplaceInList('LOV_MP_BRAND_NAME', $this->getAttributeChoice($product, "brand", "en_GB"));


        $flatProduct['CATEGORY'] = $this->getCategoryNode($this->getAttributeSimple($product, 'mkp_product_type'), 'mediamarkt');
        $flatProduct['PROD_FEAT_10990__ES_ES'] =$this->getAttributeChoice($product, 'mkp_product_type', $this->getLocale());
        
        // text
       
        $flatProduct['TITLE__ES_ES'] = $this->getAttributeSimple($product, "manufacturer_number") ?: $product['identifier'];
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
        $flatProduct["PROD_FEAT_16030"] = $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 3).' kg';


        // attribtues
        $flatProduct["PROD_FEAT_10134__ES_ES"] = implode(", ", $this->getAttributeMultiChoice($product, 'connectivity_technology', 'es_ES'));
      
        $colorName = $this->getAttributeChoice($product, "color", "es_ES");
        $flatProduct["PROD_FEAT_10812__ES_ES"] = $colorName ?: $this->getAttributeChoice($product, "color_generic", "es_ES");
        $flatProduct["PROD_FEAT_00003"] = $this->getCodeMarketplaceInList('LOV_FEAT_Color_basic', $this->getAttributeChoice($product, "color_generic", "en_GB"));
        $contentBox= $this->getAttributeSimple($product, "in_the_box", "es_ES");
        $flatProduct["PROD_FEAT_11470__ES_ES"] =  $contentBox ? strip_tags(str_replace('</li>', ', </li>', (string) $contentBox)) :'';


        // power
        $power = $this->getAttributeUnit($product, 'power', 'WATT', 3);
        $flatProduct["PROD_FEAT_16246"] = $power ? $power.' W' : null;


        //smart device
        $flatProduct["PROD_FEAT_16517"] = $this->getCodeMarketplaceInList('LOV_FEAT_SmartHome', $this->getAttributeChoice($product, "smart_home_device", "en_GB"));
        $flatProduct["PROD_FEAT_16518__ES_ES"] = implode(", ", $this->getAttributeMultiChoice($product, 'connectivity_technology', 'es_ES'));

        // audio
        $flatProduct["PROD_FEAT_11437__ES_ES"] = implode(", ", $this->getAttributeMultiChoice($product, 'speaker_type', 'es_ES'));
        $flatProduct["PROD_FEAT_10226__ES_ES"] = $this->getAttributeChoice($product, 'speaker_number', 'es_ES');
        $flatProduct["PROD_FEAT_10026"] = $this->getCodeMarketplaceInList('LOV_FEAT_Number_of_channels', $this->getAttributeChoice($product, "speaker_channels", "en_GB"));
        $powerSource = $this->getAttributeUnit($product, 'nominal_power', 'WATT', 3);
        $flatProduct["PROD_FEAT_15961"] = $powerSource ? $powerSource.' W' : null;
        
        $flatProduct["PROD_FEAT_16630"]  = $this->getCodeMarketplaceInList('LOV_FEAT_Operating_mode', $this->getAttributeChoice($product, "power_source_type", "en_GB"));


        //hair dryer
        $flatProduct["PROD_FEAT_12199__ES_ES"] = $this->getAttributeSimple($product, "number_temperature_level");
        
        // pool
        $flatProduct["PROD_FEAT_15560"] = $this->getAttributeSimple($product, "pool_size");
        
        // videoprojecteur
        $flatProduct["PROD_FEAT_14702"] = $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_14701"] = $this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_14700"] = $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0).' cm';
        $flatProduct["PROD_FEAT_14704"] = $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 3).' kg';
        $flatProduct["PROD_FEAT_10467__ES_ES"] = $this->getAttributeSimple($product, "resolution");
        $flatProduct["PROD_FEAT_10026"] = $this->getCodeMarketplaceInList('LOV_FEAT_Number_of_channels', $this->getAttributeChoice($product, "speaker_channels", "en_GB"));
        $flatProduct["PROD_FEAT_15916"] = $this->getCodeMarketplaceInList('LOV_FEAT_Image_quality', $this->getAttributeChoice($product, "image_quality", "en_GB"));

        $attriburesArea = $this->getAttributeMultiChoice($product, 'area_application', 'en_GB');
        $attriburesAreaCoverted =[];
        foreach($attriburesArea as $attribureArea) {
            $attriburesAreaCoverted[] = $this->getCodeMarketplaceInList('LOV_FEAT_Application_Beamer', $attribureArea);
        }
        $flatProduct["PROD_FEAT_16614"]= implode('|', $attriburesAreaCoverted);

        
        // pizza
        $flatProduct["PROD_FEAT_13500"] = $this->getAttributeSimple($product, "size_grilling");
        $flatProduct["PROD_FEAT_11514__ES_ES"] = $this->getAttributeSimple($product, "finish_type");
        
        // video
        $flatProduct["PROD_FEAT_13747__ES_ES"] = $this->getAttributeSimple($product, "image_rate");
        $flatProduct["PROD_FEAT_10463__ES_ES"] = $this->getAttributeSimple($product, "image_rate");
        $flatProduct["PROD_FEAT_12763__ES_ES"] = $this->getAttributeSimple($product, "image_rate");
        $flatProduct["PROD_FEAT_10963__ES_ES"] = $this->getAttributeSimple($product, "compatible_devices", 'es_ES');
        
        // battery
        $flatProduct["PROD_FEAT_13648__ES_ES"] =$this->getAttributeSimple($product, "product_certifications");
        $flatProduct["PROD_FEAT_11330__ES_ES"] = $this->getAttributeUnit($product, 'output_power', 'WATT', 0).' W';
        $flatProduct["PROD_FEAT_10928"] = $flatProduct["PROD_FEAT_16246"];

        // turntable
        $flatProduct["PROD_FEAT_15635"] =  "50";
        $flatProduct["PROD_FEAT_10061__ES_ES"] = $this->getAttributeSimple($product, "playback_speed_turntable");
        
        



        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }
}
