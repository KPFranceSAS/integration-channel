<?php

namespace App\Channels\Shopify;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\Channels\Shopify\ShopifyApiParent;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\IntegratorParent;
use App\Service\Pim\AkeneoConnector;
use Psr\Log\LoggerInterface;

abstract class ShopifySyncProductParent
{
    protected $logger;

    protected $akeneoConnector;

    protected $errors;

    protected $mailer;

    protected $businessCentralAggregator;

    protected $apiAggregator;

    protected $productsApi;
    
    public function __construct(LoggerInterface $logger, AkeneoConnector $akeneoConnector, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator, ApiAggregator $apiAggregator)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->akeneoConnector = $akeneoConnector;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->apiAggregator = $apiAggregator;
    }


    abstract public function getChannel();

    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }


    protected function getShopifyApi(): ShopifyApiParent
    {
        return $this->getApi();
    }


    public function syncProducts()
    {
    }
}
