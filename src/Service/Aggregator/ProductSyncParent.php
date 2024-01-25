<?php

namespace App\Service\Aggregator;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Pim\AkeneoConnector;
use Psr\Log\LoggerInterface;

abstract class ProductSyncParent
{
    protected $logger;

    protected $manager;

    protected $mailer;

    protected $apiAggregator;

    protected $akeneoConnector;

    protected $errors;

    protected $businessCentralAggregator;


    public function __construct(
        LoggerInterface $logger,
        AkeneoConnector $akeneoConnector,
        MailService $mailer,
        BusinessCentralAggregator $businessCentralAggregator,
        ApiAggregator $apiAggregator
    ) {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->akeneoConnector = $akeneoConnector;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->apiAggregator = $apiAggregator;
    }

    abstract public function syncProducts();

    abstract public function getChannel(): string;

    abstract protected function getProductsEnabledOnChannel();


    public function send()
    {
        try {
            $this->syncProducts();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Sync products Error class '. static::class, $e->getMessage());
        }
    }

    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }


    protected function getAxesVariation($family, $familyVariant): array
    {
        $familyVariant = $this->akeneoConnector->getFamilyVariant($family, $familyVariant);
        return $this->getAxes($familyVariant);
    }
    

    protected function getAxes(array $variantFamily): array
    {
        $axes = [];
        foreach ($variantFamily['variant_attribute_sets'] as $variantAttribute) {
            foreach ($variantAttribute['axes'] as $axe) {
                $axes[]= $axe;
            }
        }
        if ($this->getNbLevels()==1 && count($axes)==2) {
            unset($axes[0]);
            $axes= array_values($axes);
        }

        return $axes;
    }


    protected function getAttributeSimple($productPim, $nameAttribute, $locale=null)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            if ($locale) {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                    if ($attribute['locale']==$locale) {
                        return $attribute['data'];
                    }
                }
            } else {
                return  $productPim['values'][$nameAttribute][0]["data"];
            }
        }
        return null;
    }





    protected function getAttributeSimpleScopable($productPim, $nameAttribute, $scope, $locale=null)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            if ($locale) {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                   
                    if ($attribute['locale']==$locale && $attribute['scope']==$scope) {
                        return $attribute['data'];
                    }
                }
            } else {
                foreach ($productPim['values'][$nameAttribute] as $attribute) {
                   
                    if ($attribute['scope']==$scope) {
                        return $attribute['data'];
                    }
                }
            }
        }
        return null;
    }




    protected function getTranslationLabel($nameAttribute, $locale)
    {
        $attribute = $this->akeneoConnector->getAttribute($nameAttribute);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $nameAttribute;
    }



    protected $attributeOptionAkeneos= [];

   

    protected function getTranslationOption($attributeCode, $code, $locale)
    {
        if(!array_key_exists($attributeCode.'_'.$code, $this->attributeOptionAkeneos)) {
            $this->attributeOptionAkeneos[$attributeCode.'_'.$code]= $this->akeneoConnector->getAttributeOption($attributeCode, $code);
        }
        return array_key_exists($locale, $this->attributeOptionAkeneos[$attributeCode.'_'.$code]['labels']) ? $this->attributeOptionAkeneos[$attributeCode.'_'.$code]['labels'][$locale] : $code;
    }


    protected $familiesAkeneo= [];

    protected function getFamilyName($identifier, $langage)
    {
        if(!array_key_exists($identifier, $this->familiesAkeneo)) {
            $this->familiesAkeneo[$identifier]=  $this->akeneoConnector->getFamily($identifier);
        }
        return array_key_exists($langage, $this->familiesAkeneo[$identifier]['labels']) ? $this->familiesAkeneo[$identifier]['labels'][$langage] : $identifier;
    }

    protected function getTitle($productPim, $locale, $isModel=false)
    {
        if ($isModel) {
            $parentTitle = $this->getAttributeSimple($productPim, 'parent_name', $locale);
            if ($parentTitle) {
                return $parentTitle;
            }
        }

        $title = $this->getAttributeSimple($productPim, 'article_name', $locale);
        if ($title) {
            return $title;
        }

        $titleDefault = $this->getAttributeSimple($productPim, 'article_name_defaut', $locale);
        if ($titleDefault) {
            return $titleDefault;
        }

        return $this->getAttributeSimple($productPim, 'erp_name');
    }


    protected function getAttributePrice($productPim, $nameAttribute, $currency)
    {
        $valueAttribute = $this->getAttributeSimple($productPim, $nameAttribute);
        if ($valueAttribute) {
            foreach ($valueAttribute as $value) {
                if ($value['currency']==$currency) {
                    return $value["amount"];
                }
            }
        }

        return null;
    }

    protected function getAttributeChoice($productPim, $nameAttribute, $locale)
    {
        $value = $this->getAttributeSimple($productPim, $nameAttribute);
        if ($value) {
            return $this->getTranslationOption($nameAttribute, $value, $locale);
        }
        return null;
    }


    protected function getAttributeMultiChoice($productPim, $nameAttribute, $locale)
    {
        $values = $this->getAttributeSimple($productPim, $nameAttribute);
        if ($values && is_array($values) && count($values)>0) {
            $valuesPim = [];
            foreach ($values as $value) {
                $valuesPim[]=$this->getTranslationOption($nameAttribute, $value, $locale);
            }
            return $valuesPim;
        }
        return [];
    }




    protected function getAllCategories()
    {
        $this->categories=[];
        $categoriePims = $this->akeneoConnector->getAllCategories();
        foreach($categoriePims as $category) {
            $this->categories[ $category['code']] = $category;
        }
    }


    protected $categories;


    protected function getCategorieName($categoryCode, $localeCode)
    {
        if(!$this->categories) {
            $this->getAllCategories();
        }
        return $this->categories[$categoryCode]['labels'][$localeCode];
    }







    protected function getParentProduct($productModelSku)
    {
        $parent = $this->akeneoConnector->getProductModel($productModelSku);
        if ($this->getNbLevels()==1) {
            return $parent;
        } else {
            return $parent['parent'] ? $this->akeneoConnector->getProductModel($parent['parent']) : $parent;
        }
    }

    

    protected function getNbLevels()
    {
        return 2;
    }


    protected function getAttributeUnit($productPim, $nameAttribute, $unitToConvert, $nbRound)
    {
        if (array_key_exists($nameAttribute, $productPim['values'])) {
            $valueAttribute = $productPim['values'][$nameAttribute][0]['data'];
            return $valueAttribute['amount'] > 0 ? $this->transformUnit($valueAttribute["unit"], $unitToConvert, $valueAttribute['amount'], $nbRound) : 0;
        }
        return null;
    }



    protected function transformUnit($unitBase, $unitFinal, $value, $nbRound)
    {
        $factors = [
            "SQUARE_METER" =>1,
            "SQUARE_CENTIMETER" =>0.0001,
            "SQUARE_MILLIMETER" =>0.000001,
            "SQUARE_KILOMETER" =>1_000_000,

            "KILOMETER" => 1000.0,
            "METER" => 1.0,
            "DECIMETER" => 0.1,
            "CENTIMETER" => 0.01,
            "MILLIMETER" => 0.001,

            "MILLILITER" => 0.000001,
            "CENTILITER" => 0.00001,
            "LITER" => 0.001,
            "CUBIC_MILLIMETER" => 0.000000001,
            "CUBIC_CENTIMETER" => 0.000001,
            "CUBIC_DECIMETER" => 0.001,
            "CUBIC_METER" => 1.0,


            "TON" => 1000.0,
            "KILOGRAM" => 1.0,
            "GRAM" => 0.001,
            "MILLIGRAM" => 0.000001,

            "MILLIAMPEREHOUR" => 0.001,
            "AMPEREHOUR" => 1,

            "MILLIAMPERE" => 0.001,
            "CENTIAMPERE" => 0.01,
            "DECIAMPERE" => 0.1,
            "AMPERE" => 1,
            
            "WATTHOUR" => 1,
            "MILLIWATTHOUR" => 0.001,

            "WATT_CRETE" => 1,
            "KILLOWATT_CRETE" => 1000,

            "WATT" => 1,
            "KILOWATT" => 1000,
            "MEGAWATT" => 1_000_000,


        ];

        if (!array_key_exists($unitBase, $factors) || !array_key_exists($unitFinal, $factors)) {
            $this->logger->critical("Invalid units ".$unitBase." or ".$unitFinal);
            return 0;
        }
        $valueBase = $value * $factors[$unitBase];
        return round($valueBase / $factors[$unitFinal], $nbRound);
    }




    protected function isMetric($val)
    {
        return is_array($val) && array_key_exists("unit", $val);
    }

    protected function isCurrency($val)
    {
        return is_array($val) && is_array($val[0]);
    }


    protected function getAttributeColumnName($attribute, $val)
    {
        $nameAttribute=$attribute;
        if ($val['locale']) {
            $nameAttribute .= '-'. $val['locale'];
        }
        if ($val['scope']) {
            $nameAttribute .= '-'. $val['scope'];
        }
        return $nameAttribute;
    }
    



    protected function getDescription($productPim, $locale)
    {
        $description = $this->getAttributeSimple($productPim, 'description', $locale);
        if ($description) {
            return $description;
        }

        $decriptionDefault = $this->getAttributeSimple($productPim, 'description_defaut', $locale);
        if ($decriptionDefault) {
            return '<p>'.$decriptionDefault.'</p>';
        }

        return null;
    }

    protected function removeNewLine(string $text): string
    {
        return str_replace(["\r\n", "\n"], '', $text);
    }


    protected function sanitizeHtml(string $text): string
    {
        return $this->removeNewLine(strip_tags($text));
    }
}
