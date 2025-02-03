<?php

namespace App\Service\Aggregator;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\ProductStockFinder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductCorrelation;
use App\Entity\SaleChannel;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use function Symfony\Component\String\u;
use Psr\Log\LoggerInterface;

abstract class StockParent
{

    protected $manager;

    protected $integrationChannel;

    public function __construct(
        ManagerRegistry $manager,
        protected LoggerInterface $logger,
        protected MailService $mailer,
        protected BusinessCentralAggregator $businessCentralAggregator,
        protected ApiAggregator $apiAggregator,
        protected ProductStockFinder $productStockFinder
    ) {
        $this->manager = $manager->getManager();
    }

    abstract public function sendStocks();

    abstract public function checkStocks(): array;

    abstract public function getChannel();


    public function send()
    {
        try {
            /** @var \App\Entity\IntegrationChannel */
            $this->integrationChannel = $this->manager->getRepository(IntegrationChannel::class)->findOneBy([
                'code' => $this->getChannel()
            ]);

            $this->sendStocks();
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Send stock Integration - Error', $e->getMessage());
        }
    }


    public function check()
    {
        try {
            $errors = $this->checkStocks();
            if (count($errors)>0) {
                $this->mailer->sendEmailChannel($this->getChannel(), 'SKU errors', implode('<br/>', $errors));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Send stock Integration - Error', $e->getMessage());
        }
    }



    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }

    
    public function getStockProductWarehouse($sku, $depot = WebOrder::DEPOT_MONTMELO): int
    {
        $skuFinal = $this->getProductCorrelationSku($sku);
        return $this->productStockFinder->getFinalStockProductWarehouse($sku, $depot);
    }



    public function checkIfProductSellableOnChannel($sku): bool
    {
        if ($this->integrationChannel->isPriceSync()===false) {
            return true;
        }

        $skuFinal = $this->getProductCorrelationSku($sku);

        $saleChannels = $this->manager->getRepository(SaleChannel::class)->findBy([
            'integrationChannel' => $this->integrationChannel
        ]);

        $product = $this->manager->getRepository(Product::class)->findOneBySku($skuFinal);

        if($product) {
            foreach ($saleChannels as $saleChannel) {
                $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
                if ($productMarketplace->getEnabled()) {
                    return true;
                }
            }
        }
       

        return false;
    }


    public function isSkuExists($sku): int
    {
        $connector = $this->getBusinessCentralConnector(BusinessCentralConnector::KIT_PERSONALIZACION_SPORT);
        $item = $connector->getItemByNumber($sku);
        return $item!=null;
    }



    public function getBusinessCentralConnector($companyName): BusinessCentralConnector
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }


    protected function getProductCorrelationSku(string $sku): string
    {
        $skuSanitized = strtoupper($sku);
        $productCorrelation = $this->manager
                                ->getRepository(ProductCorrelation::class)
                                ->findOneBy(['skuUsed' => $skuSanitized]);
        return $productCorrelation ? $productCorrelation->getSkuErpBc() : $skuSanitized;
    }

    
    protected function isNotBundle(string $sku): bool
    {
        if (in_array($sku, [
            "FLS-FLASHLED-3PCK",
            "FLS-FLASHLED-6PCK",
            "FLS-FLASHLED-20PCK",
            "FLS-FLASHLED-64PCK"
            ])) {
            return false;
        }


        return u($sku)->containsAny('-PCK-') ? false : true;
    }
}
