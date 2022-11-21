<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\ProductSyncAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProductUpdateCommand extends Command
{
    protected static $defaultName = 'app:update-products-to';
    protected static $defaultDescription = 'Update products with the given sale channel';

    public function __construct(ProductSyncAggregator $productSyncAggregator)
    {
        $this->productSyncAggregator = $productSyncAggregator;
        parent::__construct();
    }

    private $productSyncAggregator;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper($input->getArgument('channelIntegration'));
        $productUpdater = $this->productSyncAggregator->getProductSync($channelIntegration);
        $productUpdater->syncProducts();
        return Command::SUCCESS;
    }
}
