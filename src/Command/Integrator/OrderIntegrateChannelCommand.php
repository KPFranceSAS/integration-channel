<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\IntegratorAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:integrate-orders-from', 'INtegrates all orders waiting to be invoiced with the given sale channel')]
class OrderIntegrateChannelCommand extends Command
{
    public function __construct(private readonly IntegratorAggregator $integrate)
    {
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration')
            ->addArgument('retryIntegration', InputArgument::OPTIONAL, 'To reimport all errors add 1', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper((string) $input->getArgument('channelIntegration'));

        $integrator = $this->integrate->getIntegrator($channelIntegration);
        $retryIntegration = boolval($input->getArgument('retryIntegration'));
        $integrator->processOrders($retryIntegration);
        return Command::SUCCESS;
    }
}
