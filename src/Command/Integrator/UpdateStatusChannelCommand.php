<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\UpdateStatusAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStatusChannelCommand extends Command
{
    protected static $defaultName = 'app:update-status-from';
    protected static $defaultDescription = 'Update all sale orders for the given sale channel';

    public function __construct(UpdateStatusAggregator  $updateStatusAggregator)
    {
        $this->updateStatusAggregator = $updateStatusAggregator;
        parent::__construct();
    }

    private $updateStatusAggregator;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration')
            ->addArgument('retryIntegration', InputArgument::OPTIONAL, 'To reimport all invoices add 1', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper($input->getArgument('channelIntegration'));

        $integrator = $this->updateStatusAggregator->getInvoice($channelIntegration);
        $retryIntegration = boolval($input->getArgument('retryIntegration'));
        $integrator->updateStatusSales($retryIntegration);
        return Command::SUCCESS;
    }
}
