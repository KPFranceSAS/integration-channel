<?php

namespace App\Command\Integrator;

use App\Entity\IntegrationChannel;
use App\Helper\MailService;
use App\Service\Aggregator\PriceAggregator;
use App\Service\Aggregator\ProductSyncAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-products-all', 'Update products in all channels')]
class ProductUpdateAllCommand extends Command
{
    public function __construct(
        private readonly ProductSyncAggregator $productSyncAggregator,
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
                "productSync"=>true,
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
                $this->logger->info('Start product update CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $productUpdater = $this->productSyncAggregator->getProductSync($channel);
                $productUpdater->syncProducts();
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End product update CHANNEL >>> '.$channel);
                $this->logger->info('##########################################');
                $this->logger->info('');
                $this->managerRegistry->clear();
            } catch (Exception $e) {
                $this->mailService->sendEmail("[".$channel."] Error in Product update on PIM", $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
