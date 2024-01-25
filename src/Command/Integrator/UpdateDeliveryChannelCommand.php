<?php

namespace App\Command\Integrator;

use App\Service\Aggregator\UpdateDeliveryAggregator;
use App\Service\Aggregator\UpdateStatusAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateDeliveryChannelCommand extends Command
{
    protected static $defaultName = 'app:update-delivery-from';
    protected static $defaultDescription = 'Update all delivery sale orders for the given sale channel';

    public function __construct(private readonly UpdateDeliveryAggregator  $updateStatusAggregator)
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

        $integrator = $this->updateStatusAggregator->getDelivery($channelIntegration);
        $integrator->updateStatusDeliveries();
        return Command::SUCCESS;
    }
}
