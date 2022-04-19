<?php

namespace App\Service\FitbitExpress;


use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressInvoice;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Carriers\GetTracking;
use App\Service\FitbitExpress\FitbitExpressApi;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


class FitbitExpressInvoice extends AliExpressInvoice
{

    public function getChannel()
    {
        return WebOrder::CHANNEL_FITBITEXPRESS;
    }
}
