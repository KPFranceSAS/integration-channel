<?php

namespace App\Command\Amazon;

use App\Entity\AmazonOrder;
use App\Entity\IntegrationFile;
use App\Entity\WebOrder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckInvoiceAmazonOrderCommand extends Command
{
    protected static $defaultName = 'app:amz-check-invoiced';
    protected static $defaultDescription = 'Check if amz is invoiced';

    public function __construct(ManagerRegistry $manager)
    {
        $this->manager = $manager->getManager();
        parent::__construct();
    }

    private $manager;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->manager->getRepository(AmazonOrder::class)->findBy(['integrated' => false, 'orderStatus' => 'Shipped']);
        $progressPar = new ProgressBar($output, count($orders));
        $progressPar->start();
        foreach ($orders as $order) {
            $this->checkAmzOrder($order);
            $progressPar->advance();
        }
        $this->manager->flush();
        return Command::SUCCESS;
    }


    private function checkAmzOrder(AmazonOrder $amazonOrder)
    {
        // check if INtegration file
        $orderAmz = $this->manager->getRepository(IntegrationFile::class)->findOneBy([
            "externalOrderId" => $amazonOrder->getAmazonOrderId(),
            "documentType" => IntegrationFile::TYPE_INVOICE
        ]);
        if ($orderAmz) {
            $amazonOrder->setIntegrated(true);
            $amazonOrder->setIntegrationNumber($orderAmz->getDocumentNumber());
            return true;
        }


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

        return false;
    }
}
