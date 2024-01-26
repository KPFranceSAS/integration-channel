<?php

namespace App\Command\Channels\AliExpress\AliExpress;

use App\BusinessCentral\Connector\GadgetIberiaConnector;
use App\Channels\AliExpress\AliExpress\AliExpressApi;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use Doctrine\Persistence\ManagerRegistry;
use OrderQuery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:aliexpress-build-historic', 'Build historical orders for aliexpress')]
class BuildHistoricCommand extends Command
{
    public function __construct(private readonly GadgetIberiaConnector $bcConnector, ManagerRegistry $manager, private readonly AliExpressApi $aliExpressApi)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

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

        $notFounds = [];

        $orders = $this->aliExpressApi->getAllOrders($param0);

        $progressPar = new ProgressBar($output, count($orders));
        $progressPar->start();
        $counter = 0;
        foreach ($orders as $order) {
            if ($this->checkIfImport($order->order_id)) {
                $invoice = $this->bcConnector->getSaleInvoiceByExternalNumber($order->order_id);
                if ($invoice) {
                    $orderApi = $this->aliExpressApi->getOrder($order->order_id);
                    $webOrder = WebOrder::createOneFrom($orderApi, IntegrationChannel::CHANNEL_ALIEXPRESS);
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
                    $notFounds[] = $order->order_id;
                }
            } else {
                $output->writeln('Already imported ' . $order->order_id);
            }
            if ($counter % 30 == 0) {
                $output->writeln('Flushed ');
                $this->manager->flush();
                $this->manager->clear();
            }
            $counter++;
            $progressPar->advance();
        }
        $this->manager->flush();
        $progressPar->finish();
        foreach ($notFounds as $notFound) {
            $output->writeln($notFound);
        }
        return Command::SUCCESS;
    }



    private function checkIfImport($orderId)
    {
        $orders = $this->manager->getRepository(WebOrder::class)->findBy(['channel' => IntegrationChannel::CHANNEL_ALIEXPRESS, 'externalNumber' => $orderId]);
        return count($orders) == 0;
    }
}
