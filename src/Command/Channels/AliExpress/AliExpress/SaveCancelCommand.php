<?php

namespace App\Command\Channels\AliExpress\AliExpress;

use App\Channels\AliExpress\AliExpressApiParent;
use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Utils\DatetimeUtils;
use App\Service\Aggregator\ApiAggregator;
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


    public function __construct(
        ManagerRegistry $manager,
        ApiAggregator $apiAggregator,
        LoggerInterface $logger,
        MailService $mailService,
        GadgetIberiaConnector $gadgetIberiaConnector
    ) {
        parent::__construct();
        $this->apiAggregator = $apiAggregator;
        $this->logger = $logger;
        $this->gadgetIberiaConnector = $gadgetIberiaConnector;
        $this->manager = $manager->getManager();
        $this->mailService = $mailService;
    }


    protected function getApi(): AliExpressApiParent
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }

    protected $manager;

    protected $gadgetIberiaConnector;

    protected $apiAggregator;

    protected $logger;

    protected $mailService;

    protected $errors = [];



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $webOrders = $this->manager->getRepository(WebOrder::class)
                ->findBy(
                    [
                        'status' => WebOrder::STATE_SYNC_TO_ERP,
                        'channel' =>  $this->getChannel()
                    ]
                );
        foreach ($webOrders as $webOrder) {
            $this->checkOrderStatus($webOrder);
        }

        $webOrdersCancel = $this->manager->getRepository(WebOrder::class)
                ->findBy(
                    [
                        'status' => WebOrder::STATE_ERROR,
                        'channel' => $this->getChannel()
                    ]
                );
        foreach ($webOrdersCancel as $webOrderCancel) {
            $this->checkOrderStatus($webOrderCancel);
        }

        $this->manager->flush();

        if (count($this->errors) > 0) {
            $this->mailService->sendEmailChannel($this->getChannel(), 'Order Cancelation  Error', implode("<br/>", $this->errors));
        }


        return Command::SUCCESS;
    }


    protected function getChannel()
    {
        return WebOrder::CHANNEL_ALIEXPRESS;
    }


    protected function checkOrderStatus(WebOrder $webOrder)
    {
        $orderAliexpress = $this->getApi()->getOrder($webOrder->getExternalNumber());
        if (
            $orderAliexpress->order_status == 'FINISH'
            && $orderAliexpress->order_end_reason == "cancel_order_close_trade"
        ) {
            $reason =  'Order has been cancelled after acceptation  online on '
            . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end);
            $this->cancelSaleOrder($webOrder, $reason);
        } elseif (
            $orderAliexpress->order_status == 'FINISH'
            && $orderAliexpress->order_end_reason == "seller_send_goods_timeout"
        ) {
            $reason =  'Order has been cancelled online because delay of expedition is out of delay on '
            . DatetimeUtils::createStringTimeFromDate($orderAliexpress->gmt_trade_end);
            $this->cancelSaleOrder($webOrder, $reason);
        }
    }


    protected function cancelSaleOrder(WebOrder $webOrder, $reason)
    {
        $this->addLog($webOrder, $reason);
        $this->errors[] = $webOrder . '  has been cancelled';
        $this->errors[] = $reason;

        $webOrder->setStatus(WebOrder::STATE_CANCELLED);
        $saleOrder = $this->gadgetIberiaConnector->getSaleOrderByNumber($webOrder->getOrderErp());
        if ($saleOrder) {
            try {
                $result = $this->gadgetIberiaConnector->deleteSaleOrder($saleOrder['id']);
                $this->addLog($webOrder, 'Sale order ' . $webOrder->getOrderErp() . ' have been deleted');
            } catch (Exception $e) {
                $this->errors[] = 'Deleting the sale order ' . $webOrder->getOrderErp()
                                    . ' did not succeeded ' . $e->getMessage();
            }
        } else {
            $this->addLog($webOrder, 'Sale order ' . $webOrder->getOrderErp() . ' have already been deleted');
        }


        $saleInvoice = $this->gadgetIberiaConnector->getSaleInvoiceByOrderNumber($webOrder->getOrderErp());
        if ($saleInvoice) {
            $invoiceCreationProblem = 'Invoice ' . $saleInvoice['number']
                                        . ' has been created. Check with warehouse the state of the shipment';
            $this->errors[] = $invoiceCreationProblem;
            $this->addLog($webOrder, $invoiceCreationProblem);
        } else {
            $this->addLog($webOrder, 'No sale invoice have already been created');
        }
    }


    protected function addLog(WebOrder $webOrder, $log)
    {
        $webOrder->addLog($log);
        $this->logger->info($log);
    }
}
