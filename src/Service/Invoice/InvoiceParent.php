<?php

namespace App\Service\Invoice;


use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;


abstract class InvoiceParent
{


    protected $logger;

    protected $manager;

    protected $mailer;

    protected $businessCentralAggregator;


    public function __construct(ManagerRegistry $manager, LoggerInterface $logger, MailService $mailer, BusinessCentralAggregator $businessCentralAggregator)
    {
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailer = $mailer;
        $this->businessCentralAggregator = $businessCentralAggregator;
    }


    abstract public function getChannel();




    public function getBusinessCentralConnector($companyName)
    {
        return $this->businessCentralAggregator->getBusinessCentralConnector($companyName);
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
            $businessCentralConnector   = $this->getBusinessCentralConnector($order->getCompany());
            $invoice =  $businessCentralConnector->getSaleInvoiceByOrderNumber($order->getOrderErp());
            if ($invoice) {
                $order->cleanErrors();
                $postInvoice = $this->postInvoice($order, $invoice);
                if ($postInvoice) {
                    $this->addLogToOrder($order, 'Invoice created in the ERP with number ' . $invoice['number']);
                    $order->setInvoiceErp($invoice['number']);
                    $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                    $order->setStatus(WebOrder::STATE_INVOICED);
                }
            } else {
                if ($order->hasDelayTreatment()) {
                    $messageDelay = $order->getDelayProblemMessage();
                    if ($order->haveNoLogWithMessage($messageDelay)) {
                        $this->addLogToOrder($order, $messageDelay);
                        $this->addError($messageDelay);
                    }
                }
            }
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $order->addError($message);
            $this->addError($order->getExternalNumber() . ' >> ' . $message);
        }
        $this->manager->flush();
    }


    protected function postInvoice(WebOrder $order, $invoice)
    {
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






    protected function addLogToOrder(WebOrder $webOrder, $message)
    {
        $webOrder->addLog($message);
        $this->logger->info($message);
    }


    protected function addError($errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }
}
