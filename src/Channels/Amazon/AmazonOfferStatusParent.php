<?php

namespace App\Channels\Amazon;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\Amazon\AmazonApiParent;
use App\Entity\ProductSaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class AmazonOfferStatusParent
{
    /**@var EntityManager */
    protected $manager;


    public function __construct(
        ManagerRegistry $manager,
        protected LoggerInterface $logger,
        protected MailService $mailer,
        protected BusinessCentralAggregator $businessCentralAggregator,
        protected ApiAggregator $apiAggregator,
    ) {
        $this->manager = $manager->getManager();
    }

    abstract public function getChannel(): string;


    protected function getAmazonApi(): AmazonApiParent
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }


    public function checkAllProducts()
    {

       
    }






   

}
