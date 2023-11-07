<?php

namespace App\Channels\Mirakl\Decathlon;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;
use League\HTMLToMarkdown\HtmlConverter;

class DecathlonSyncProduct extends MiraklSyncProductParent
{
    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', ['decathlon_fr_kp'])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }

    

    protected function flatProduct(array $product):array
    {
        $this->logger->info('Flat product '.$product['identifier']);
        $flatProduct = [
            'ProductIdentifier' => $product['identifier'],
            'ean_codes' => $this->getAttributeSimple($product, 'ean'),
            'main_image' => $this->getAttributeSimple($product, 'image_url_1'),
            'mainTitle' => $this->getAttributeSimple($product, 'erp_name'),
        ];


        $flatProduct["brandName"] = $this->getCodeMarketplaceInList('brandName', $this->getAttributeChoice($product, "brand", "en_GB"));

        $flatProduct["color"] = $this->getCodeMarketplaceInList('color', $this->getAttributeChoice($product, "color_generic", "en_GB"));
        $flatProduct["CHARACTERISTIC_575"] = $this->getCodeMarketplaceInList('values-575', $this->getAttributeUnit($product, 'product_width', 'CENTIMETER', 0).' cm');
        $flatProduct["CHARACTERISTIC_398"] = $this->getCodeMarketplaceInList('values-398', $this->getAttributeUnit($product, 'product_width', 'CENTIMETER', 0).' cm');
        $flatProduct["CHARACTERISTIC_569"] = $this->getCodeMarketplaceInList('values-569', $this->getAttributeUnit($product, 'product_height', 'CENTIMETER', 0).' cm');

        $flatProduct["CHARACTERISTIC_590"] = $this->getCodeMarketplaceInList('values-590', $this->getAttributeUnit($product, 'product_weight', 'CENTIMETER', 0).' kg');


        
        

        for ($i = 2; $i <= 7;$i++) {
            $flatProduct['image_'.$i] = $this->getAttributeSimple($product, 'image_url_'.$i);
        }

        $locales = [
            'en_GB',
            'de_DE',
            'it_IT',
            'fr_FR',
            'es_ES'
        ];

        $localizablesTextFields= [
            'productTitle' => 'article_name',
            'webcatchline' => 'short_description',
            'longDescription' => 'description',
            'storageAdvice' => 'storage_advice',
            'video1' => 'howto_video_url_1',
        ];
   


        foreach ($localizablesTextFields as $localizableMirakl => $localizablePim) {
            foreach ($locales as $loc) {
                $value = $this->getAttributeSimple($product, $localizablePim, $loc);
                if ($value) {
                    if ($localizableMirakl=='longDescription') {
                        $converter = new HtmlConverter();
                        $valueFormate = str_replace(['~', '<hr>', '<hr/>'], ['-', '<hr><p></p>', '<hr><p></p>'], $value);
                        $description = $converter->convert($valueFormate);
                        
                        if (strlen($description)>5000) {
                            $description= substr($description, 0, 5000);
                        }
                        $flatProduct[$localizableMirakl.'-'.$loc] = $description;
                    } elseif ($localizableMirakl=='productTitle') {
                        $flatProduct[$localizableMirakl.'-'.$loc] = substr($this->sanitizeHtml($value), 0, 80);
                    } elseif ($localizableMirakl=='webcatchline') {
                        $flatProduct[$localizableMirakl.'-'.$loc] = substr($this->sanitizeHtml($value), 0, 200);
                    } else {
                        $flatProduct[$localizableMirakl.'-'.$loc] = $this->sanitizeHtml($value);
                    }
                }
            }
        }


        $equivalences = [
            "marketplace_solar_panel_energy_travel"=>	"30061",
            "marketplace_generator_energy_travel"=>	"30060",
            "marketplace_garden_spa_home"=>"N-1148912",
            "marketplace_video_projectors_video"=>	"10309",
            "marketplace_camera_video"=>	"30041",
            "marketplace_accessories_video"=>"N-300351",
            "marketplace_camera_battery"=>	"30059",
            "marketplace_camera_waterproof_accessories"=>"40014",
            "marketplace_camera_charger"=>"N-300581",
            "marketplace_travel_oven"=>"10346"
        ];

        foreach($equivalences as $pimCategory => $mmCategory) {
            if(in_array($pimCategory, $product['categories'])) {
                $flatProduct['category'] = $mmCategory;
                break;
            }
        }

       


        if(array_key_exists('category', $flatProduct)) {
            switch($flatProduct['category']) {
                case '30061':
                    $flatProduct ['PRODUCT_TYPE'] = "solar panel";
                    break;
                case '30060':
                    $flatProduct ['PRODUCT_TYPE'] = "power bank";
                    break;
                case 'N-1148912':
                    $flatProduct ['PRODUCT_TYPE'] = "aspirateur piscine";
                    $flatProduct ['SPORT_69'] = "50";
                    break;
                case '10309':
                    $flatProduct ['PRODUCT_TYPE'] = "26258";
                    $flatProduct ['SPORT_6'] = "191";
                    break;
                case '30041':
                    $flatProduct ['PRODUCT_TYPE'] = "25201";
                    break;
                case '10346':
                    $flatProduct ['SPORT_6'] = "331";
                    break;
            };
            

           
        } else {
            $this->logger->info('Product not categorized');
        }

     
        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
