<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\PriceStockAggregator;
use App\Service\Aggregator\StockAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-stocks-all', 'Update stocks in all channels')]
class StockUpdateAllCommand extends Command
{
    public function __construct(private readonly StockAggregator $stockAggregator,
    private readonly PriceStockAggregator $priceStockAggregator, 
    ManagerRegistry $managerRegistry, 
    private readonly LoggerInterface $logger, private readonly MailService $mailService)
    {
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelCodes = $this->managerRegistry->getRepository(IntegrationChannel::class)->findBy(
            [
                "active"=>true,
                "stockSync"=>true,
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
                $this->logger->info('Start stock update CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $stockUpdate = $this->stockAggregator->getStock($channel);
                if($stockUpdate){
                    $stockUpdate->send();
                } else {
                    $this->logger->critical('No stock update CHANNEL >>> '.$channel);
                }
                
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End stock update CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $this->managerRegistry->clear();
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in StockUpdateAllCommand '.$channel, $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
