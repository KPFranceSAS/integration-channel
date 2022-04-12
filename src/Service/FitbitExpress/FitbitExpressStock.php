<?php

namespace App\Service\FitbitExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressStock;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\FitbitExpress\FitbitExpressApi;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;

class FitbitExpressStock  extends AliExpressStock
{

    public function __construct(
        FilesystemOperator $awsStorage,
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        FitbitExpressApi $fitbitExpress,
        BusinessCentralAggregator $businessCentralAggregator
    ) {

        parent::__construct($awsStorage, $manager, $logger, $mailer, $fitbitExpress, $businessCentralAggregator);
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
