<?php

namespace App\Service\BusinessCentral;

use App\Service\BusinessCentral\BusinessCentralAggregator;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class GetStatusDelivery
{
    protected $logger;

    protected $businessCentralAggregator;

    protected $manager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        $this->logger = $logger;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->manager = $managerRegistry->getManager();
    }
}
