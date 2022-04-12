<?php

namespace App\Service\Invoice;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressInvoice;
use App\Service\ChannelAdvisor\SendInvoicesToChannelAdvisor;
use App\Service\FitbitExpress\FitbitExpressInvoice;
use App\Service\Invoice\InvoiceParent;
use App\Service\OwletCare\OwletCareInvoice;
use Exception;

class InvoiceAggregator
{

    private $aliExpressInvoice;

    private $sendInvoicesToChannelAdvisor;

    private $owletCareInvoice;

    private $fitbitExpressInvoice;

    public function __construct(SendInvoicesToChannelAdvisor $sendInvoicesToChannelAdvisor, AliExpressInvoice $aliExpressInvoice, OwletCareInvoice $owletCareInvoice, FitbitExpressInvoice $fitbitExpressInvoice)
    {
        $this->sendInvoicesToChannelAdvisor = $sendInvoicesToChannelAdvisor;
        $this->aliExpressInvoice = $aliExpressInvoice;
        $this->fitbitExpressInvoice = $fitbitExpressInvoice;
        $this->owletCareInvoice = $owletCareInvoice;
    }


    public  function getInvoice(string $channel): InvoiceParent
    {

        if ($channel == WebOrder::CHANNEL_CHANNELADVISOR) {
            return $this->sendInvoicesToChannelAdvisor;
        }

        if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return $this->aliExpressInvoice;
        }

        if ($channel == WebOrder::CHANNEL_OWLETCARE) {
            return $this->owletCareInvoice;
        }

        if ($channel == WebOrder::CHANNEL_FITBITEXPRESS) {
            return $this->fitbitExpressInvoice;
        }

        throw new Exception("Channel $channel is not related to any invoice");
    }
}
