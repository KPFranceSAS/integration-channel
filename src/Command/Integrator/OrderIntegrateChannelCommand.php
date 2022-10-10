<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\IntegratorAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderIntegrateChannelCommand extends Command
{
    protected static $defaultName = 'app:integrate-orders-from';
    protected static $defaultDescription = 'INtegrates all orders waiting to be invoiced with the given sale channel';

    public function __construct(IntegratorAggregator $integrate)
    {
        $this->integrate = $integrate;
        parent::__construct();
    }

    private $integrate;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration')
            ->addArgument('retryIntegration', InputArgument::OPTIONAL, 'To reimport all errors add 1', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper($input->getArgument('channelIntegration'));

        $integrator = $this->integrate->getIntegrator($channelIntegration);
        $retryIntegration = boolval($input->getArgument('retryIntegration'));
        $integrator->processOrders($retryIntegration);
        return Command::SUCCESS;
    }
}
