<?php

namespace App\Service\Aggregator;

use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class PriceParent
{
    protected $logger;

    protected $manager;

    protected $mailer;

    protected $apiAggregator;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        ApiAggregator $apiAggregator
    ) {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->apiAggregator = $apiAggregator;
    }

    abstract public function sendPrices(array $products, array $saleChannels);

    abstract public function getChannel(): string;


    public function send()
    {
        try {
            $integrationChannel = $this->manager->getRepository(IntegrationChannel::class)->findBy([
                'code' => $this->getChannel()
            ]);

            $saleChannels = $this->manager->getRepository(SaleChannel::class)->findBy([
                'integrationChannel' => $integrationChannel
            ]);

            $products = $this->manager->getRepository(Product::class)->findAll();
            $this->sendPrices($products, $saleChannels);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Update prices Error class '. get_class($this), $e->getMessage());
        }
    }

    protected $productMarketplaces;

    protected function organisePriceSaleChannel($products, $saleChannels)
    {
        $this->productMarketplaces = [];
        foreach ($products as $product) {
            foreach ($saleChannels as $saleChannel) {
                $productMarketplace = $product->getProductSaleChannelByCode($saleChannel->getCode());
                if ($productMarketplace->getEnabled()) {
                    $this->productMarketplaces[$product->getSku()]=$productMarketplace;
                }
            }
        }
    }


    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }
}
