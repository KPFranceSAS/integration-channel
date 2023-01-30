<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\PriceAggregator;
use App\Service\Aggregator\PriceStockAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PriceUpdateCommand extends Command
{
    protected static $defaultName = 'app:update-prices-to';
    protected static $defaultDescription = 'Update prices with the given sale channel';

    public function __construct(PriceAggregator $priceAggregator, PriceStockAggregator $priceStockAggregator)
    {
        $this->priceAggregator = $priceAggregator;
        $this->priceStockAggregator = $priceStockAggregator;
        parent::__construct();
    }

    private $priceAggregator;

    private $priceStockAggregator;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper($input->getArgument('channelIntegration'));
        $priceUpdater = $this->priceAggregator->getPrice($channelIntegration);
        if(!$priceUpdater){
            $priceUpdater = $this->priceStockAggregator->getPriceStock($channelIntegration);
        }
        $priceUpdater->send();
        return Command::SUCCESS;
    }
}
