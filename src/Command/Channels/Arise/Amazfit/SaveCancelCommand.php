<?php

namespace App\Command\Channels\Arise\Amazfit;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\Arise\AriseApiParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaveCancelCommand extends Command
{
    protected static $defaultName = 'app:arise-amazfit-cancel-orders';
    protected static $defaultDescription = 'Retrieve all Amazfit orders cancelled online';


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


    protected function getApi(): AriseApiParent
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
        /** @var array[\App\Entity\WebOrder] */
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


        $this->manager->flush();

        if (count($this->errors) > 0) {
            $this->mailService->sendEmailChannel($this->getChannel(), 'Order Cancelation  Error', implode("<br/>", $this->errors));
        }


        return Command::SUCCESS;
    }


    protected function getChannel()
    {
        return IntegrationChannel::CHANNEL_AMAZFIT_ARISE;
    }


    protected function checkOrderStatus(WebOrder $webOrder)
    {
        $orderArise = $this->getApi()->getOrder($webOrder->getExternalNumber());
        foreach ($orderArise->lines as $line) {
            if ($line->status == 'canceled') {
                $this->cancelSaleOrder($webOrder, $line->cancel_return_initiator. '>'.$line->reason);
                return;
            }
        }
    }


    protected function cancelSaleOrder(WebOrder $webOrder, $reason)
    {
        $this->addLog($webOrder, $reason);
        $this->errors[] = $webOrder . '  has been cancelled for the following reason : '.$reason;

        $webOrder->setStatus(WebOrder::STATE_CANCELLED);
        $saleOrder = $this->gadgetIberiaConnector->getSaleOrderByNumber($webOrder->getOrderErp());
        if ($saleOrder) {
            try {
                $result = $this->gadgetIberiaConnector->deleteSaleOrder($saleOrder['id']);
                $this->addLog($webOrder, 'Sale order ' . $webOrder->getOrderErp() . ' have been deleted');
                $this->errors[] = 'Sale order ' . $webOrder->getOrderErp() . ' have been deleted';
            } catch (Exception $e) {
                $this->errors[] = 'Deleting the sale order ' . $webOrder->getOrderErp() . ' did not succeeded because a shipment should be processing.';
                $this->errors[] = 'You need to advise warehouse to stop shipment.';
            }
        } else {
            $this->addLog($webOrder, 'Sale order ' . $webOrder->getOrderErp() . ' have already been deleted');
        }


        $saleInvoice = $this->gadgetIberiaConnector->getSaleInvoiceByOrderNumber($webOrder->getOrderErp());
        if ($saleInvoice) {
            $invoiceCreationProblem = 'Invoice ' . $saleInvoice['number'].' has been created. Check with warehouse the state of the shipment and to stop it.';
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
