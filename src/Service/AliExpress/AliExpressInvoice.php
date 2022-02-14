<?php

namespace App\Service\AliExpress;


use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Invoice\InvoiceParent;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


class AliExpressInvoice extends InvoiceParent
{

    private $aliExpressApi;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        AliExpressApi $aliExpressApi,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator);
        $this->aliExpressApi = $aliExpressApi;
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }




    protected function postInvoice(WebOrder $order, $invoice)
    {
    }
}
