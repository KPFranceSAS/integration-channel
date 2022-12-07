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
            $this->mailer->sendEmailChannel($this->getChannel(), 'Sync products Error class '. get_class($this), $e->getMessage());
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


    protected function getTranslationLabel($nameAttribute, $locale)
    {
        $attribute = $this->akeneoConnector->getAttribute($nameAttribute);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $nameAttribute;
    }


   

    protected function getTranslationOption($attributeCode, $code, $locale)
    {
        $attribute = $this->akeneoConnector->getAttributeOption($attributeCode, $code);
        return array_key_exists($locale, $attribute['labels']) ? $attribute['labels'][$locale] : $code;
    }


    protected function getFamilyName($identifier, $langage)
    {
        $family =  $this->akeneoConnector->getFamily($identifier);
        return array_key_exists($langage, $family['labels']) ? $family['labels'][$langage] : $identifier;
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


    protected function getAttributeUnit($productPim, $nameAttribute, $unitToConvert)
    {

        if (array_key_exists($nameAttribute, $productPim['values'])) {

            $valueAttribute = $productPim['values'][$nameAttribute][0]['data'];
            return $valueAttribute['amount'] > 0 ? $this->transformUnit($valueAttribute["unit"], $unitToConvert, $valueAttribute['amount']) : 0;
        }
        return null;
    }



    protected function transformUnit($unitBase, $unitFinal, $value)
    {
        $factors = [
            "KILOMETER" => 1000.0,
            "METER" => 1.0,
            "DECIMETER" => 0.1,
            "CENTIMETER" => 0.01,
            "MILLIMETER" => 0.001,
            "TON" => 1000.0,
            "KILOGRAM" => 1.0,
            "GRAM" => 0.001,
            "MILLIGRAM" => 0.000001,
            
        ];

        if (!array_key_exists($unitBase, $factors) || !array_key_exists($unitFinal, $factors)) {
            echo "Invalid units";
            return;
        }
    
        // convert the value to base
        $value_in_meters = $value * $factors[$unitBase];
    
        // convert the value from base to the desired unit
        $converted_value = $value_in_meters / $factors[$unitFinal];
    
        return $converted_value;
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
}
