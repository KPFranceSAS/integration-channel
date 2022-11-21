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

class ProductUpdateAllCommand extends Command
{
    protected static $defaultName = 'app:update-products-all';
    protected static $defaultDescription = 'Update products in all channels';

    public function __construct(
        ProductSyncAggregator $productSyncAggregator,
        ManagerRegistry $managerRegistry,
        LoggerInterface $logger,
        MailService $mailService
    ) {
        $this->productSyncAggregator = $productSyncAggregator;
        $this->logger = $logger;
        $this->mailService = $mailService;
        $this->managerRegistry = $managerRegistry->getManager();
        parent::__construct();
    }

    private $managerRegistry;

    private $productSyncAggregator;

    private $logger;

    private $mailService;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channels = $this->managerRegistry->getRepository(IntegrationChannel::class)->findBy(
            [
                "active"=>true,
                "productSync"=>true,
            ]
        );
        foreach ($channels as $channel) {
            try {
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('Start product update CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
                $productUpdater = $this->productSyncAggregator->getProductSync($channel->getCode());
                $productUpdater->syncProducts();
                $this->logger->info('');
                $this->logger->info('##########################################');
                $this->logger->info('End product update CHANNEL >>> '.$channel->getCode());
                $this->logger->info('##########################################');
                $this->logger->info('');
            } catch (Exception $e) {
                $this->mailService->sendEmail('Error in ProductUpdateAllCommand', $e->getMessage());
            }
        }

        
        return Command::SUCCESS;
    }
}
