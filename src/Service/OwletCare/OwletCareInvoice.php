<?php

namespace App\Service\OwletCare;


use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Invoice\InvoiceParent;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;


class OwletCareInvoice extends InvoiceParent
{

    private $owletCareApi;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        OwletCareApi $owletCareApi,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct($manager, $logger, $mailer, $businessCentralAggregator);
        $this->owletCareApi = $owletCareApi;
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_OWLETCARE;
    }




    protected function postInvoice(WebOrder $order, $invoice)
    {
        return true;
    }
}
