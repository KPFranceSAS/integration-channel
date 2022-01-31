<?php

namespace App\Service\Invoice;

use App\Entity\WebOrder;
use App\Service\ChannelAdvisor\SendInvoicesToChannelAdvisor;
use App\Service\Invoice\InvoiceParent;
use Exception;

class InvoiceAggregator
{



    private $sendInvoicesToChannelAdvisor;

    public function __construct(SendInvoicesToChannelAdvisor $sendInvoicesToChannelAdvisor)
    {
        $this->sendInvoicesToChannelAdvisor = $sendInvoicesToChannelAdvisor;
    }


    public  function getInvoice(string $channel): InvoiceParent
    {

        if ($channel == WebOrder::CHANNEL_CHANNELADVISOR) {
            return $this->sendInvoicesToChannelAdvisor;
        }

        throw new Exception("Channel $channel is not related to any invoice");
    }
}
