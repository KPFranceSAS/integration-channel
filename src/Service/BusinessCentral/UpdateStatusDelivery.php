<?php

namespace App\Service\BusinessCentral;

use App\Service\BusinessCentral\BusinessCentralAggregator;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class UpdateStatusDelivery
{
    protected $logger;

    protected $businessCentralAggregator;

    protected $awsStorage;


    public function __construct(
        FilesystemOperator $awsStorage,
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->logger = $logger;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->awsStorage = $awsStorage;
        $this->manager = $managerRegistry->getManager();
    }



    public function processOrders()
    {
    }
}
