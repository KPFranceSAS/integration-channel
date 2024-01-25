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
        private readonly PriceAggregator $priceAggregator,
        private readonly PriceStockAggregator $priceStockAggregator,
        ManagerRegistry $managerRegistry,
        private readonly LoggerInterface $logger,
        private readonly MailService $mailService
    ) {
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelCodes = $this->managerRegistry->getRepository(IntegrationChannel::class)->findBy(
            [
                "active"=>true,
                "priceSync"=>true,
            ]
        );


        $channels=[];
        foreach ($channelCodes as $channelCode) {
            $channels[]=$channelCode->getCode();
        }


        foreach ($channels as $channel) {
            try {
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('Start price update CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                    
                $priceUpdater = $this->priceAggregator->getPrice($channel);
                if(!$priceUpdater){
                    $priceUpdater = $this->priceStockAggregator->getPriceStock($channel);
                }
                $priceUpdater->send();
                
                
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End price update CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $this->managerRegistry->clear();
                
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in PriceUpdateAllCommand '.$channel, $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
