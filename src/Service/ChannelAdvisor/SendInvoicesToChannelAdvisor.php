<?php

namespace App\Service\ChannelAdvisor;


use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralConnector;
use App\Service\ChannelAdvisor\ChannelWebservice;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;


/**
 * Services that will get through the API the order from ChannelAdvisor
 * 
 */
class SendInvoicesToChannelAdvisor
{

    /**
     *
     * @var  LoggerInterface
     */
    private $logger;

    /**
     *
     * @var ChannelWebservice
     */
    private $channel;

    /**
     *
     * @var  ObjectManager
     */
    private $manager;

    /**
     *
     * @var BusinessCentralConnector
     */
    private $businessCentralConnector;


    public function __construct(
        ManagerRegistry $manager,
        LoggerInterface $logger,
        MailService $mailer,
        ChannelWebservice $channel,
        BusinessCentralConnector $businessCentralConnector
    ) {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->channel = $channel;
        $this->manager = $manager->getManager();
        $this->channel = $channel;
        $this->businessCentralConnector = $businessCentralConnector;
    }


    /**
     * 
     * 
     * @return void
     */
    public function processOrders()
    {
        try {
            $this->errors = [];
            $this->logger->info('Start sending invoices');
            $this->sendInvoices();
            $this->logger->info('Ended sending invoices');



            if (count($this->errors) > 0) {
                throw new \Exception(implode('<br/>', $this->errors));
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->mailer->sendEmail('[Invoice Send] Error', $e->getMessage(), 'stephane.lanjard@kpsport.com');
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
                'status' => WebOrder::STATE_SYNC_TO_ERP,
                "channel" => WebOrder::CHANNEL_CHANNELADVISOR
            ]
        );
        $this->logger->info(count($ordersToSend) . ' orders to re-send');
        foreach ($ordersToSend as $orderToSend) {
            $this->logger->info('>>> Sending file to channel of order ' . $orderToSend->getExternalNumber());
            $this->sendInvoice($orderToSend);
        }
    }



    protected function addError($errorMessage)
    {
        $this->logger->error($errorMessage);
        $this->errors[] = $errorMessage;
    }


    protected function sendInvoice(WebOrder $order)
    {
        try {

            $invoice =  $this->businessCentralConnector->getSaleInvoiceByOrderNumber($order->getOrderErp());
            if ($invoice) {
                $this->addLogToOrder($order, 'Invoice created in the ERP with number ' . $invoice['number']);
                $order->setStatus(WebOrder::STATE_INVOICED);
                $order->setInvoiceErp($invoice['number']);
                $order->setErpDocument(WebOrder::DOCUMENT_INVOICE);

                $this->addLogToOrder($order, 'Retrieve invoice content');
                $contentPdf  = $this->businessCentralConnector->getContentInvoicePdf($invoice['id']);
                $this->addLogToOrder($order, 'Retrieved invoice content');

                $orderApi = $order->getOrderContent();


                $this->addLogToOrder($order, 'Start sending invoice to Channel Advisor');
                $sendFile = $this->channel->sendInvoice($orderApi->ProfileID, $orderApi->ID, $invoice['totalAmountIncludingTax'], $invoice['totalTaxAmount'], $order->getInvoiceErp(), $contentPdf);
                if (!$sendFile) {
                    throw new \Exception('Upload  was not done uploaded on ChannelAdvisor for ' . $order->getInvoiceErp());
                }
                $this->addLogToOrder($order, 'Invoice send to channel Advisor');
            } else {
                $this->addLogToOrder($order, 'Invoice not yet created');
            }
        } catch (Exception $e) {
            $message =  mb_convert_encoding($e->getMessage(), "UTF-8", "UTF-8");
            $order->addError($message);
            $order->setStatus(WebOrder::STATE_ERROR_INVOICE);
            $this->addError($message);
        }
        $this->manager->flush();
    }






    protected function addLogToOrder(WebOrder $webOrder, $message)
    {
        $webOrder->addLog($message);
        $this->logger->info("|__" . $message);
    }
}
