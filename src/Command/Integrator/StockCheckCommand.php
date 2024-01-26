<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\StockAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:check-stocks-to', 'Check items with the given sale channel')]
class StockCheckCommand extends Command
{
    public function __construct(private readonly StockAggregator $stockAggregator)
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
        $stockUtil = $this->stockAggregator->getStock($channelIntegration);
        if($stockUtil){
            $stockUtil->check();
        }
        
        return Command::SUCCESS;
    }
}
