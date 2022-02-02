<?php

namespace App\Service\ChannelAdvisor;


use App\Entity\WebOrder;
use App\Service\BusinessCentral\KpFranceConnector;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\Invoice\InvoiceParent;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;


/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class SendInvoicesToChannelAdvisor extends InvoiceParent
{

    const DELAI_MAX = 24;


    private $channel;



    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        ChannelWebservice $channel,
        KpFranceConnector $kpFranceConnector
    ) {
        parent::__construct($manager, $logger, $mailer, $kpFranceConnector);
        $this->channel = $channel;
    }

    public function getChannel()
    {
        return WebOrder::CHANNEL_CHANNELADVISOR;
    }




    protected function sendInvoice(WebOrder $order)
    {
        try {

            $invoice =  $this->businessCentralConnector->getSaleInvoiceByOrderNumber($order->getOrderErp());
            if ($invoice) {
                $order->cleanErrors();
                $this->addLogToOrder($order, 'Invoice created in the ERP with number ' . $invoice['number']);
                $this->addLogToOrder($order, 'Retrieve invoice content ' . $invoice['number']);
                $contentPdf  = $this->businessCentralConnector->getContentInvoicePdf($invoice['id']);
                $this->addLogToOrder($order, 'Retrieved invoice content ' . $invoice['number']);


                $this->addLogToOrder($order, 'Start sending invoice to Channel Advisor');
                $orderApi = $order->getOrderContent();

                $sendFile = $this->channel->sendInvoice($orderApi->ProfileID, $orderApi->ID, $invoice['totalAmountIncludingTax'], $invoice['totalTaxAmount'], $invoice['number'], $contentPdf);
                if (!$sendFile) {
                    throw new \Exception('Upload  was not done uploaded on ChannelAdvisor for ' . $invoice['number']);
                }
                $this->addLogToOrder($order, 'Invoice sent to channel Advisor');
                $order->setInvoiceErp($invoice['number']);
                $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                $order->setStatus(WebOrder::STATE_INVOICED);
            } else {
                $this->addLogToOrder($order, 'Invoice has been not yet created in Business Central');
                $delay = $order->getNbHoursSinceCreation();
                $messageDelay = 'Delay is overpassed';
                if ($delay > self::DELAI_MAX && $order->haveNoLogWithMessage($messageDelay)) {
                    $this->addLogToOrder($order, $messageDelay);
                    $this->addError("Delay max is overpassed ($delay hours of integration)  for the order " . $order->getExternalNumber());
                }
            }
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $order->addError($message);
            $this->addError($order->getExternalNumber() . ' >> ' . $message);
        }
        $this->manager->flush();
    }
}
