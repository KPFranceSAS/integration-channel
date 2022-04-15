<?php

namespace App\Service\Invoice;


use App\Entity\WebOrder;
use App\Helper\Utils\DatetimeUtils;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Carriers\GetTracking;
use App\Service\MailService;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;


abstract class InvoiceParent
{


    protected $logger;

    protected $manager;

    protected $mailer;

    protected $businessCentralAggregator;

    protected $tracker;


    public function __construct(ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator, GetTracking $tracker)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->tracker = $tracker;
    }


    abstract public function getChannel();




    public function getBusinessCentralConnector($companyName)
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
    }


    protected function getTracking(WebOrder $order, $invoice)
    {
        $tracking = $this->tracker->getTracking($order->getCompany(), $invoice['number']);
        if (!$tracking) {
            $this->logger->info('Invoice ' . $invoice['number'] . ' is not present in the file');
        } else {
            if ($this->isATrackingNumber($tracking['Tracking number'])) {
                return $tracking;
            } else {
                $logNotFoundTracking = 'Tracking number is not yet retrieved from DHL for expedition ' . $tracking['Tracking number'];
                if ($order->haveNoLogWithMessage($logNotFoundTracking)) {
                    $this->addLogToOrder($order, $logNotFoundTracking);
                } else {
                    $this->logger->info($logNotFoundTracking);
                }
                $trackingFromWeb =  $this->tracker->getDhlTracking($tracking['Tracking number']);
                if ($trackingFromWeb) {
                    $tracking['Tracking number'] = $trackingFromWeb;
                    return $tracking;
                }
            }
        }

        return null;
    }


    protected function isATrackingNumber($trackingNumber): bool
    {
        // Gadeget iberia expedition
        if (substr($trackingNumber, 0, 5) == 'GALV2') {
            return false;
        }
        // kps expedition
        if (substr($trackingNumber, 0, 4) == 'ALV2') {
            return false;
        }
        // kp france expedition
        if (substr($trackingNumber, 0, 5) == 'ALVF2') {
            return false;
        }
        return true;
    }



    /**
     * 
     * 
     * @return void
     */
    public function processInvoices($reprocess = false)
    {
        try {
            $this->errors = [];
            if ($reprocess) {
                $this->logger->info('Start sending invoices ' . $this->getChannel() . ' in error');
                $this->resendInvoices();
                $this->logger->info('Ended sending invoices ' . $this->getChannel() . ' in error');
            } else {
                $this->logger->info('Start sending invoices ' . $this->getChannel());
                $this->sendInvoices();
                $this->logger->info('Ended sending invoices ' . $this->getChannel());
            }


            if (count($this->errors) > 0) {
                throw new \Exception(implode('<br/><br/>', $this->errors));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmail('[Invoice Send ' . $this->getChannel() . ' ] Error', $e->getMessage());
        }
    }


    public function logLine($message)
    {
        $separator = str_repeat("-", strlen($message));
        $this->logger->info('');
        $this->logger->info($separator);
        $this->logger->info($message);
        $this->logger->info($separator);
    }



    protected function sendInvoice(WebOrder $order)
    {
        try {
            $invoice =  $this->getInvoiceNumber($order);
            if ($invoice) {
                $order->cleanErrors();
                $postInvoice = $this->postInvoice($order, $invoice);
                $logMessageInvoice = 'Invoice created in the ERP with number ' . $invoice['number'] . ' on ' . $invoice['invoiceDate'];
                if ($order->haveNoLogWithMessage($logMessageInvoice)) {
                    $this->addLogToOrder($order, $logMessageInvoice);
                } else {
                    $this->logger->info($logMessageInvoice);
                }
                if ($postInvoice) {
                    $order->setInvoiceErp($invoice['number']);
                    $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                    $order->setStatus(WebOrder::STATE_INVOICED);
                } else {
                    $this->checkInvoiceIsLate($order, $invoice);
                }
            } else {
                $this->checkOrderIsLate($order);
            }
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $this->addErrorToOrder($order, $order->getExternalNumber() . ' >> ' . $message);
        }
        $this->manager->flush();
    }


    protected function postInvoice(WebOrder $order, $invoice)
    {
        return true;
    }


    protected function getInvoiceNumber(WebOrder $order)
    {
        $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
        $invoice =  $businessCentralConnector->getSaleInvoiceByOrderNumber($order->getOrderErp());
        if ($invoice) {
            $this->logger->info("Invoice found by reference to the order number " . $order->getOrderErp());
            return $invoice;
        }

        $invoice =  $businessCentralConnector->getSaleInvoiceByExternalDocumentNumberCustomer($order->getExternalNumber(), $order->getCustomerNumber());

        if ($invoice) {
            $this->logger->info("Invoice found by reference to the order external " . $order->getExternalNumber() . " and customer number " . $order->getCustomerNumber());
            return $invoice;
        }

        $this->logger->info("Invoice not found for moment " . $order->getOrderErp() . " for " . $order->getCustomerNumber());
        return null;
    }




    protected function checkOrderIsLate(WebOrder $order)
    {
        $this->logger->info('Check if order is late ');
        if ($order->hasDelayTreatment()) {
            $messageDelay = $order->getDelayProblemMessage();
            if ($order->haveNoLogWithMessage($messageDelay)) {
                $this->addErrorToOrder($order, $messageDelay);
            }
        }
    }


    public function checkInvoiceIsLate(WebOrder $order, $invoice)
    {
        $this->logger->info('Check if late ' . $invoice['number'] . " >> " . $invoice['invoiceDate']);
        $invoiceDate = DateTime::createFromFormat('Y-m-d H:i', $invoice['invoiceDate'] . ' 16:00');
        $now = new DateTime();
        $interval = $now->diff($invoiceDate, true);
        $nbHours = $interval->format('%a') * 24 + $interval->format('%h');
        if ($nbHours > 34) {
            $messageDelay = $order . ' has been sent with the invoice ' . $invoice['number'] . ' but no tracking is retrieved. Please confirm tracking on ' . $this->getChannel();
            if ($order->haveNoLogWithMessage($messageDelay)) {
                $this->addErrorToOrder($order, $messageDelay);
            }
        }
    }



    /**
     * 
     * 
     * @return void
     */
    protected function sendInvoices()
    {
        $ordersToSend = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                "status" => WebOrder::STATE_SYNC_TO_ERP,
                "channel" => $this->getChannel(),
            ]
        );
        $this->logger->info(count($ordersToSend) . ' invoices to send');
        foreach ($ordersToSend as $orderToSend) {
            $this->logLine('>>> Sending invoice ' . $orderToSend->getExternalNumber());
            $this->sendInvoice($orderToSend);
        }
    }





    /**
     * 
     * 
     * @return void
     */
    protected function resendInvoices()
    {
        $ordersToSend = $this->manager->getRepository(WebOrder::class)->findBy(
            [
                "status" => WebOrder::STATE_ERROR_INVOICE,
                "channel" => $this->getChannel(),
            ]
        );
        $this->logger->info(count($ordersToSend) . ' orders to re-send');
        foreach ($ordersToSend as $orderToSend) {
            $this->logLine('>>> Sending invoice of order ' . $orderToSend->getExternalNumber());
            $this->sendInvoice($orderToSend);
        }
    }


    protected function addLogToOrder(WebOrder $webOrder, string $message)
    {
        $webOrder->addLog($message);
        $this->logger->info($message);
    }



    protected function addErrorToOrder(WebOrder $webOrder, string $message)
    {
        $webOrder->addError($message);
        $this->addError($message);
    }


    protected function addError(string $errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }
}
