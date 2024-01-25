<?php

namespace App\Command\Integrator;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
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

abstract class SaveCancelCommand extends Command
{
    public function __construct(
        ManagerRegistry $manager,
        ApiAggregator $apiAggregator,
        LoggerInterface $logger,
        MailService $mailService,
        BusinessCentralAggregator $businessCentralAggregator
    ) {
        parent::__construct();
        $this->apiAggregator = $apiAggregator;
        $this->logger = $logger;
        $this->businessCentralAggregator = $businessCentralAggregator;
        $this->manager = $manager->getManager();
        $this->mailService = $mailService;
    }


    protected function getApi()
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }

    protected $manager;

    protected $businessCentralAggregator;

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
            $this->mailService->sendEmailChannel($this->getChannel(), 'Order Cancelation Report', implode("<br/>", $this->errors));
        }


        return Command::SUCCESS;
    }


    abstract protected function getChannel();


    abstract protected function checkOrderStatus(WebOrder $webOrder);
   

    protected function cancelSaleOrder(WebOrder $webOrder, $reason)
    {
        $bcConnector = $this->businessCentralAggregator->getBusinessCentralConnector($webOrder->getCompany());

        $this->addLog($webOrder, $reason);
        $this->errors[] = $webOrder . '  has been cancelled for the following reason : '.$reason;

        $webOrder->setStatus(WebOrder::STATE_CANCELLED);
        $saleOrder = $bcConnector->getSaleOrderByNumber($webOrder->getOrderErp());
        if ($saleOrder) {
            try {
                $result = $bcConnector->deleteSaleOrder($saleOrder['id']);
                $this->addLog($webOrder, 'Sale order ' . $webOrder->getOrderErp() . ' have been deleted');
                $this->errors[] = 'Sale order ' . $webOrder->getOrderErp() . ' have been deleted';
            } catch (Exception) {
                $this->errors[] = 'Deleting the sale order ' . $webOrder->getOrderErp() . ' did not succeeded because a shipment should be processing.';
                $this->errors[] = 'You need to advise warehouse to stop shipment.';
            }
        } else {
            $this->addLog($webOrder, 'Sale order ' . $webOrder->getOrderErp() . ' is not present');
        }


        $saleInvoice = $bcConnector->getSaleInvoiceByOrderNumber($webOrder->getOrderErp());
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
