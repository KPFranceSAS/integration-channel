<?php

namespace App\Command\Amazon;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\Entity\AmazonOrder;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-check-invoiced', 'Check if amz is invoiced')]
class CheckInvoiceAmazonOrderCommand extends Command
{
    public function __construct(ManagerRegistry $manager, private readonly KpFranceConnector $kpfranceConnector)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->manager->getRepository(AmazonOrder::class)->findBy(
            [
                'integrated' => false,
                'orderStatus' => 'Shipped',
                'itemStatus' => 'Shipped'
            ]
        );
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
        /** @var \App\Entity\WebOrder */
        $orderAmz = $this->manager->getRepository(WebOrder::class)->findOneBy([
            "externalNumber" => $amazonOrder->getAmazonOrderId(),
            "channel" => IntegrationChannel::CHANNEL_CHANNELADVISOR,
            "status" => WebOrder::STATE_COMPLETE
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