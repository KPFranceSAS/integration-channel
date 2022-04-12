<?php

namespace App\Service\FitbitExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressIntegrateOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class FitbitExpressIntegrateOrder extends AliExpressIntegrateOrder
{

    const FITBITEXPRESS_CUSTOMER_NUMBER = "003253";

    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        FitbitExpressApi $aliExpress,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($manager, $logger, $mailer, $aliExpress, $businessCentralAggregator);
    }


    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }


    protected function getClientNumber()
    {
        return FitbitExpressIntegrateOrder::FITBITEXPRESS_CUSTOMER_NUMBER;
    }
}
