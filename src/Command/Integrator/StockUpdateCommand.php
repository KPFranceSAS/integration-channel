<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\StockAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StockUpdateCommand extends Command
{
    protected static $defaultName = 'app:update-stocks-to';
    protected static $defaultDescription = 'Update am with the given sale channel';

    public function __construct(StockAggregator $stockAggregator)
    {
        $this->stockAggregator = $stockAggregator;
        parent::__construct();
    }

    private $stockAggregator;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper($input->getArgument('channelIntegration'));
        $stockUtil = $this->stockAggregator->getStock($channelIntegration);
        $stockUtil->send();
        return Command::SUCCESS;
    }
}
