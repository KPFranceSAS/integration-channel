<?php

namespace App\Service\Invoice;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressInvoice;
use App\Service\ChannelAdvisor\SendInvoicesToChannelAdvisor;
use App\Service\Invoice\InvoiceParent;
use Exception;

class InvoiceAggregator
{

    private $aliExpressInvoice;

    private $sendInvoicesToChannelAdvisor;

    public function __construct(SendInvoicesToChannelAdvisor $sendInvoicesToChannelAdvisor, AliExpressInvoice $aliExpressInvoice)
    {
        $this->sendInvoicesToChannelAdvisor = $sendInvoicesToChannelAdvisor;
        $this->aliExpressInvoice = $aliExpressInvoice;
    }


    public  function getInvoice(string $channel): InvoiceParent
    {

        if ($channel == WebOrder::CHANNEL_CHANNELADVISOR) {
            return $this->sendInvoicesToChannelAdvisor;
        }

        if ($channel == WebOrder::CHANNEL_ALIEXPRESS) {
            return $this->aliExpressInvoice;
        }

        throw new Exception("Channel $channel is not related to any invoice");
    }
}
