<?php

namespace App\Command\Channels\Arise;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\Arise\AriseApiParent;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AriseMarkAsDeliveryCommand extends Command
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


    protected function getApi():AriseApiParent
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
        $orderArises = $this->getApi()->getAllOrdersShipped();

        foreach ($orderArises as $orderArise) {
            $this->checkOrderStatus($orderArise);           
        }
        return Command::SUCCESS;
    }


    abstract protected function getChannel();


    


    protected function checkOrderStatus(stdClass $orderArise)
    {
        $webOrderArise = $this->manager->getRepository(WebOrder::class)->findOneBy([
            'channel'=>$this->getChannel(),
            'fulfilledBy'=> WebOrder::FULFILLED_BY_SELLER,
            'status' => WebOrder::STATE_INVOICED,
            'externalNumber'=>$orderArise->order_id
        ]); 

        if(!$webOrderArise){
            $this->logger->info('Not order in this case...');
        }

        $statutExpedition = $webOrderArise->getStatusExpedition();
        if($statutExpedition && $statutExpedition['FechaEntrega']){
            $this->logger->info('Is delivered '.$statutExpedition['FechaEntrega'].' > '.$statutExpedition['Numero']);
            $markOk =  $this->getApi()->markOrderAsDelivered($orderArise->order_id);
            if($markOk){
                $webOrderArise->addLog('Mark as delivered on '.$statutExpedition['FechaEntrega']);
            }
        }else{
            $this->logger->info('Is not yet delivered');
        }
        
    }

}
