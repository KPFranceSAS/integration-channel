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
            $this->mailer->sendEmailChannel($this->getChannel(), 'Update prices Error class'. $this, $e->getMessage());
        }
    }

    public function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }
}
