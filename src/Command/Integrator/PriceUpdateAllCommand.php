<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\PriceAggregator;
use App\Service\Aggregator\PriceStockAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PriceUpdateAllCommand extends Command
{
    protected static $defaultName = 'app:update-prices-all';
    protected static $defaultDescription = 'Update prices in all channels';

    public function __construct(
        PriceAggregator $priceAggregator,
        PriceStockAggregator $priceStockAggregator,
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger,
        MailService $mailService
    ) {
        $this->priceAggregator = $priceAggregator;
        $this->priceStockAggregator = $priceStockAggregator;
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;

    private $priceAggregator;

    private $priceStockAggregator;

    private $logger;

    private $mailService;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channels = $this->managerRegistry->getRepository(IntegrationChannel::class)->findBy(
            [
                "active"=>true,
                "priceSync"=>true,
            ]
        );
        foreach ($channels as $channel) {
            try {
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('Start price update CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
                    
                $priceUpdater = $this->priceAggregator->getPrice($channel->getCode());
                if(!$priceUpdater){
                    $priceUpdater = $this->priceStockAggregator->getPriceStock($channel->getCode());
                }
                $priceUpdater->send();
                
                
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End price update CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in PriceUpdateAllCommand', $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
