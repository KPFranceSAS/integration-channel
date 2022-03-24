<?php

namespace App\Command\AliExpress;

use App\Entity\WebOrder;
use App\Service\AliExpress\AliExpressApi;
use App\Service\MailService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaveCancelCommand extends Command
{
    protected static $defaultName = 'app:aliexpress-cancel-orders';
    protected static $defaultDescription = 'Retrieve all Aliexpress orders cancelled online';





    public function __construct(ManagerRegistry $manager, AliExpressApi $aliExpressApi, LoggerInterface $logger, MailService $mailService)
    {
        parent::__construct();
        $this->aliExpressApi = $aliExpressApi;
        $this->logger = $logger;
        $this->manager = $manager->getManager();
        $this->mailService = $mailService;
    }

    private $manager;

    private $aliExpressApi;

    private $logger;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $webOrders = $this->manager->getRepository(WebOrder::class)->findBy(['status' => WebOrder::STATE_SYNC_TO_ERP, 'channel' => WebOrder::CHANNEL_ALIEXPRESS]);
        foreach ($webOrders as $webOrder) {
            $this->checkOrderStatus($webOrder);
        }
        $this->manager->flush();

        return Command::SUCCESS;
    }



    private function checkOrderStatus(WebOrder $webOrder)
    {
        $orderAliexpress = $this->aliExpressApi->getOrder($webOrder->getExternalNumber());
        if ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "cancel_order_close_trade") {
            $webOrder->addLog('Order has been cancelled online on ' . $orderAliexpress->gmt_trade_end);
            $this->logger->info('Order has been cancelled online ' . $webOrder);
            $webOrder->setStatus(WebOrder::STATE_CANCELLED);
        }

        if ($orderAliexpress->order_status == 'FINISH' && $orderAliexpress->order_end_reason == "seller_send_goods_timeout") {
            $webOrder->addLog('Order has been cancelled online because delay of expedition is out of delay on ' . $orderAliexpress->gmt_trade_end);
            $this->logger->info('Order has been cancelled online because delay of expedition is out of delay on ' . $webOrder);
            $webOrder->setStatus(WebOrder::STATE_CANCELLED);
        }
    }
}
