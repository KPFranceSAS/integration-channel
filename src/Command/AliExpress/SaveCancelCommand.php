<?php

namespace App\Command\AliExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressApi;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaveCancelCommand extends Command
{
    protected static $defaultName = 'app:aliexpress-cancel-orders';
    protected static $defaultDescription = 'Retrieve all Aliexpress orders cancelled online';





    public function __construct(ManagerRegistry $manager, AliExpressApi $aliExpressApi, LoggerInterface $logger, MailService $mailService, GadgetIberiaConnector $gadgetIberiaConnector)
    {
        parent::__construct();
        $this->aliExpressApi = $aliExpressApi;
        $this->logger = $logger;
        $this->gadgetIberiaConnector = $gadgetIberiaConnector;
        $this->manager = $manager->getManager();
        $this->mailService = $mailService;
    }

    private $manager;

    private $gadgetIberiaConnector;

    private $aliExpressApi;

    private $logger;

    private $errors = [];



    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $webOrders = $this->manager->getRepository(WebOrder::class)->findBy(['status' => WebOrder::STATE_SYNC_TO_ERP, 'channel' => WebOrder::CHANNEL_ALIEXPRESS]);
        foreach ($webOrders as $webOrder) {
            $this->checkOrderStatus($webOrder);
        }

        $webOrdersCancel = $this->manager->getRepository(WebOrder::class)->findBy(['status' => WebOrder::STATE_ERROR, 'channel' => WebOrder::CHANNEL_ALIEXPRESS]);
        foreach ($webOrdersCancel as $webOrderCancel) {
            $this->checkOrderStatus($webOrderCancel);
        }

        $this->manager->flush();

        if (count($this->errors) > 0) {
            $this->mailer->sendEmail('[Order Cancelation ALIEXPRESS] Error', implode("<br/>", $this->errors));
        }


        return Command::SUCCESS;
    }



    private function checkOrderStatus(WebOrder $webOrder)
    {
        $orderAliexpress = $this->aliExpressApi->getOrder($webOrder->getExternalNumber());
        if ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "cancel_order_close_trade") {
            $reason =  'Order has been cancelled after acceptation  online on ' . $orderAliexpress->gmt_trade_end;
            $this->cancelSaleOrder($webOrder, $reason);
        } elseif ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "seller_send_goods_timeout") {
            $reason =  'Order has been cancelled online because delay of expedition is out of delay on ' . $orderAliexpress->gmt_trade_end;
            $this->cancelSaleOrder($webOrder, $reason);
        }
    }


    private function cancelSaleOrder(WebOrder $webOrder, $reason)
    {
        $this->addLog($webOrder, $reason);
        $this->errors[] = $webOrder->__toString() . '  has been cancelled';
        $this->errors[] = $reason;

        $webOrder->setStatus(WebOrder::STATE_CANCELLED);
        $saleOrder = $this->gadgetIberiaConnector->getSaleOrderByNumber($webOrder->getOrderErp());
        if ($saleOrder) {
            try {
                $result = $this->gadgetIberiaConnector->deleteSaleOrder($saleOrder['id']);
                $this->addLog($webOrder, 'Sale order have been deleted');
            } catch (Exception $e) {
                $this->errors[] = 'Deleting the sale order ' . $webOrder->getOrderErp() . ' did not succeeded ' . $e->getMessage();
            }
        } else {
            $this->addLog($webOrder, 'Sale order have already been deleted');
        }


        $saleInvoice = $this->gadgetIberiaConnector->getSaleInvoiceByOrderNumber($webOrder->getOrderErp());
        if ($saleInvoice) {
            $this->errors[] = 'Invoice has been created. Check with warehouse the state of the shipment';
        } else {
            $this->addLog($webOrder, 'No sale invoice have already been created');
        }
    }


    private function addLog(WebOrder $webOrder, $log)
    {
        $webOrder->addLog($log);
        $this->logger->info($log);
    }
}
