<?php

namespace App\Command\AliExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressApi;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use Doctrine\Persistence\ManagerRegistry;
use OrderQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildHistoricCommand extends Command
{
    protected static $defaultName = 'app:aliexpress-build-historic';
    protected static $defaultDescription = 'Build historical orders for aliexpress';

    public function __construct(GadgetIberiaConnector $saleOrderConnector, ManagerRegistry $manager, AliExpressApi $aliExpressApi)
    {
        $this->aliExpressApi = $aliExpressApi;
        $this->manager = $manager->getManager();
        $this->bcConnector = $saleOrderConnector;
        parent::__construct();
    }

    private $bcConnector;

    private $aliExpressApi;

    private $manager;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('dateStart', InputArgument::REQUIRED, 'Start Date Y-m-d')
            ->addArgument('dateEnd', InputArgument::REQUIRED, 'End Date Y-m-d');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $param0 = new OrderQuery();
        $param0->create_date_start = $input->getArgument('dateStart') . " 00:00:00";
        $param0->create_date_end = $input->getArgument('dateEnd') . " 00:00:00";
        $param0->order_status = "FINISH";

        $orders = $this->aliExpressApi->getAllOrders($param0);

        $progressPar = new ProgressBar($output, count($orders));
        $progressPar->start();

        foreach ($orders as $order) {

            if ($this->checkIfImport($order->order_id)) {
                $invoice = $this->bcConnector->getSaleInvoiceByExternalNumber($order->order_id);
                if ($invoice) {
                    $orderApi = $this->aliExpressApi->getOrder($order->order_id);
                    $webOrder = WebOrder::createOneFrom($orderApi, WebOrder::CHANNEL_ALIEXPRESS);
                    $this->manager->persist($webOrder);
                    $webOrder->setCompany($this->bcConnector->getCompanyName());
                    $webOrder->setStatus(WebOrder::STATE_INVOICED);
                    $webOrder->setOrderErp($invoice['orderNumber']);
                    $webOrder->setInvoiceErp($invoice['number']);
                    $webOrder->setErpDocument(WebOrder::DOCUMENT_INVOICE);
                    $webOrder->addLog('Build from historic transaction');
                    $output->writeln('Importation ' . $order->order_id);
                } else {
                    $output->writeln('No invoice found for ' . $order->order_id);
                }
            } else {
                $output->writeln('Alreday imported ' . $order->order_id);
            }
            $progressPar->advance();
        }
        $this->manager->flush();
        $progressPar->finish();
        return Command::SUCCESS;
    }



    private function checkIfImport($orderId)
    {
        $orders = $this->manager->getRepository(WebOrder::class)->findBy(['channel' => WebOrder::CHANNEL_ALIEXPRESS, 'externalNumber' => $orderId]);
        return count($orders) == 0;
    }
}
