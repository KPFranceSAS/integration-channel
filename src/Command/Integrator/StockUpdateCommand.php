<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\StockAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-stocks-to', 'Update am with the given sale channel')]
class StockUpdateCommand extends Command
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
        $stockUpdate = $this->stockAggregator->getStock($channelIntegration);
        if($stockUpdate){
            $stockUpdate->send();
        }
        return Command::SUCCESS;
    }
}
