<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\StockAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:check-stocks-all', 'Check skus in all channels to ensure sku mapping is OK')]
class StockCheckAllCommand extends Command
{
    public function __construct(private readonly StockAggregator $stockAggregator, ManagerRegistry $managerRegistry, private readonly LoggerInterface $logger, private readonly MailService $mailService)
    {
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channels = $this->managerRegistry->getRepository(IntegrationChannel::class)->findBy(
            [
                "active"=>true,
                "stockSync"=>true,
            ]
        );
        foreach ($channels as $channel) {
            try {
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('Start stock update CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
                $stockUpdate = $this->stockAggregator->getStock($channel->getCode());
                if($stockUpdate){
                    $stockUpdate->check();
                }
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End stock update CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in StockCheckAllCommand', $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
