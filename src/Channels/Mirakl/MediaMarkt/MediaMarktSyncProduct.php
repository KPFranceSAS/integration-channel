<?php

namespace App\Channels\Mirakl\MediaMarkt;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;

class MediaMarktSyncProduct extends MiraklSyncProductParent
{
      

   
    protected function flatProduct(array $product):array
    {
        $flatProduct = parent::flatProduct($product);
        $flatProduct['PROD_FEAT_10990__ES_ES'] =$this->getAttributeChoice($product, 'mkp_product_type', 'es_ES');
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
        $flatProduct["PROD_FEAT_11470__ES_ES"] =  $contentBox ? strip_tags(str_replace('</li>', ', </li>', (string) $contentBox)) :'<li>1 x '.$this->getAttributeSimple($product, "article_name", 'es_ES').'</li>';
        
        return $flatProduct;
       


        
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }



    protected function getMarketplaceNode(): string
    {
        return 'mediamarkt';
    }

    



    public function getLocales(): array
    {
        return [
            'es_ES', 'en_GB'
        ];
    }


}
