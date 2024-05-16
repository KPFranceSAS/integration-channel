<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;
use League\HTMLToMarkdown\HtmlConverter;

class DecathlonSyncProduct extends MiraklSyncProductParent
{
       

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
        $flatProduct["CHARACTERISTIC_575"] = $this->getCodeMarketplaceInList('values-575', $this->getAttributeUnit($product, 'package_lenght', 'CENTIMETER', 0).' cm');
        $flatProduct["CHARACTERISTIC_398"] = $this->getCodeMarketplaceInList('values-398', $this->getAttributeUnit($product, 'package_width', 'CENTIMETER', 0).' cm');
        $flatProduct["CHARACTERISTIC_569"] = $this->getCodeMarketplaceInList('values-569', $this->getAttributeUnit($product, 'package_height', 'CENTIMETER', 0).' cm');
        $flatProduct["CHARACTERISTIC_590"] = $this->getCodeMarketplaceInList('values-590', $this->getAttributeUnit($product, 'package_weight', 'KILOGRAM', 0).' kg');
        

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
                        $valueFormate = str_replace(['~', '<hr>', '<hr/>'], ['-', '<hr><p></p>', '<hr><p></p>'], (string) $value);
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

        $flatProduct['category'] = $this->getCategoryNode($this->getAttributeSimple($product, 'product_type'), 'decathlon');

        
        switch($flatProduct['category']) {
            case '30061':
                $flatProduct ['PRODUCT_TYPE'] = "solar panel";
                break;
            case '10343':
                $flatProduct ['PRODUCT_TYPE_10343'] = "26296";
                $flatProduct ['SPORT_6'] = "655";
                $flatProduct ['SIZE_10'] = "Z132_33L";
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

     
        return $flatProduct;
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
