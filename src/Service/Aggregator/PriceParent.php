<?php

namespace App\Service\Aggregator;

use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductSaleChannel;
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

    abstract public function sendPrices(array $saleChannels);

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

            $this->sendPrices($saleChannels);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmailChannel($this->getChannel(), 'Update prices Error class '. get_class($this), $e->getMessage());
        }
    }

    protected $productMarketplaces;

    protected function organisePriceSaleChannel($saleChannels)
    {
        $products = $this->getFilteredProducts($saleChannels);
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

    protected function getFilteredProducts($saleChannels): array
    {
        $productsFiltererd=[];
        foreach($saleChannels as $saleChannel) {
            $productMarketplaces = $this->manager->getRepository(ProductSaleChannel::class)->findBy(
                [
                    'saleChannel'=> $saleChannel,
                    'enabled' => true
                ]
            );

            foreach ($productMarketplaces as $productMarketplace) {
                $product = $productMarketplace->getProduct();
                $productsFiltererd[$product->getSku()] = $product;
            }
        }

        return array_values($productsFiltererd);
    }



    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }
}
