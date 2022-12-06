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
}
