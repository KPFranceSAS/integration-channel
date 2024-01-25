<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\StockAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StockCheckCommand extends Command
{
    protected static $defaultName = 'app:check-stocks-to';
    protected static $defaultDescription = 'Check items with the given sale channel';

    public function __construct(private readonly StockAggregator $stockAggregator)
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
        $stockUtil = $this->stockAggregator->getStock($channelIntegration);
        if($stockUtil){
            $stockUtil->check();
        }
        
        return Command::SUCCESS;
    }
}
