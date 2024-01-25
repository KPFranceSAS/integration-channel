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
        $stockUpdate = $this->stockAggregator->getStock($channelIntegration);
        if($stockUpdate){
            $stockUpdate->send();
        }
        return Command::SUCCESS;
    }
}
