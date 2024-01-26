<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\ProductSyncAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-products-to', 'Update products with the given sale channel')]
class ProductUpdateCommand extends Command
{
    public function __construct(private readonly ProductSyncAggregator $productSyncAggregator)
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper((string) $input->getArgument('channelIntegration'));
        $productUpdater = $this->productSyncAggregator->getProductSync($channelIntegration);
        $productUpdater->syncProducts();
        return Command::SUCCESS;
    }
}
