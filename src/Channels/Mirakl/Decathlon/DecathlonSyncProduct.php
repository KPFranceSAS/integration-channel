<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklSyncProductParent;
use App\Entity\IntegrationChannel;
use League\HTMLToMarkdown\HtmlConverter;

class DecathlonSyncProduct extends MiraklSyncProductParent
{
    protected function flatProduct(array $product):array
    {

        $flatProduct = parent::flatProduct($product);

        $localizablesTextFields= [
            'productTitle' => 'article_name',
            'webcatchline' => 'short_description',
            'longDescription' => 'description',
            'video1' => 'howto_video_url_1',
        ];



        foreach ($localizablesTextFields as $localizableMirakl => $localizablePim) {
            foreach ($this->getLocales() as $loc) {
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




        return $flatProduct;

    }
    
   



    protected function getMarketplaceNode(): string
    {
        return 'decathlon';
    }

  



    public function getLocales(): array
    {
        return [
            'en_GB',
            'de_DE',
            'it_IT',
            'fr_FR',
            'es_ES',
            'pt_PT'
        ];
    }



    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }
}
