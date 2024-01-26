<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\PriceAggregator;
use App\Service\Aggregator\PriceStockAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-prices-to', 'Update prices with the given sale channel')]
class PriceUpdateCommand extends Command
{
    public function __construct(private readonly PriceAggregator $priceAggregator, private readonly PriceStockAggregator $priceStockAggregator)
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper((string) $input->getArgument('channelIntegration'));
        $priceUpdater = $this->priceAggregator->getPrice($channelIntegration);
        if(!$priceUpdater){
            $priceUpdater = $this->priceStockAggregator->getPriceStock($channelIntegration);
        }
        $priceUpdater->send();
        return Command::SUCCESS;
    }
}
