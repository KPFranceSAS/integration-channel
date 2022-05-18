<?php

namespace App\Command\Amazon;

use App\Entity\AmazonOrder;
use App\Entity\IntegrationFile;
use App\Entity\WebOrder;
use App\Service\BusinessCentral\KpFranceConnector;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckInvoiceAmazonOrderCommand extends Command
{
    protected static $defaultName = 'app:amz-check-invoiced';
    protected static $defaultDescription = 'Check if amz is invoiced';

    public function __construct(ManagerRegistry $manager, KpFranceConnector $kpfranceConnector)
    {
        $this->manager = $manager->getManager();
        $this->kpfranceConnector = $kpfranceConnector;
        parent::__construct();
    }

    private $manager;

    private $kpfranceConnector;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->manager->getRepository(AmazonOrder::class)->findBy(['integrated' => false, 'orderStatus' => 'Shipped', 'itemStatus' => 'Shipped']);
        $progressPar = new ProgressBar($output, count($orders));
        $progressPar->start();
        foreach ($orders as $order) {
            $check = $this->checkAmzOrder($order);
            if ($check) {
                $output->writeln($order->getAmazonOrderId() . ' >> integrated ' . $order->getIntegrationNumber());
            } else {
                $output->writeln($order->getAmazonOrderId() . ' >> not found ');
            }
            if ($progressPar->getProgress() % 100 == 0) {
                $this->manager->flush();
            }
            $progressPar->advance();
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }


    private function checkAmzOrder(AmazonOrder $amazonOrder)
    {

        // check if WebOrder file
        $orderAmz = $this->manager->getRepository(WebOrder::class)->findOneBy([
            "externalNumber" => $amazonOrder->getAmazonOrderId(),
            "channel" => WebOrder::CHANNEL_CHANNELADVISOR,
            "status" => WebOrder::STATE_INVOICED
        ]);
        if ($orderAmz) {
            $amazonOrder->setIntegrated(true);
            $amazonOrder->setIntegrationNumber($orderAmz->getInvoiceErp());
            return true;
        }


        $invoice = $this->kpfranceConnector->getSaleInvoiceByExternalNumber($amazonOrder->getAmazonOrderId());
        if ($invoice) {
            $amazonOrder->setIntegrated(true);
            $amazonOrder->setIntegrationNumber($invoice['number']);
            return true;
        }


        return false;
    }
}
