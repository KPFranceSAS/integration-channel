<?php

namespace App\Channels\FnacDarty;

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

        $attributeImageLoc = $this->getAttributeSimple($product, 'image_url_loc_1', 'fr_FR');
        $flatProduct["IMAGE|1505-1"] = $attributeImageLoc ? $attributeImageLoc : $this->getAttributeSimple($product, 'image_url_1');

        for ($i = 2; $i <= 4;$i++) {
            $j=$i-1;
            $attributeImageLoc = $this->getAttributeSimple($product, 'image_url_loc_'.$i, 'fr_FR');
            $flatProduct["IMAGE|3-".$j] = $attributeImageLoc ? $attributeImageLoc : $this->getAttributeSimple($product, 'image_url_'.$i);
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

        $flatProduct['Typology'] = $this->getCategoryNode($this->getAttributeSimple($product, 'mkp_product_type'), 'fnacDarty');

        return $flatProduct;
    }



    protected function getMarketplaceNode(): string
    {
        return 'fnacDarty';
    }


    public function getLocales(): array
    {
        return [
            'en_GB',
            'fr_FR',
        ];
    }









}
