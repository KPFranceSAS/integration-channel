<?php

namespace App\Command\Integrator;

use App\Service\BusinessCentral\GetStatusDelivery;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateStatusDeliveryCommand extends Command
{
    protected static $defaultName = 'app:update-status-delivery';
    protected static $defaultDescription = 'Update status from file in the ERP';

    public function __construct(GetStatusDelivery $updateStatusDelivery)
    {
        $this->updateStatusDelivery = $updateStatusDelivery;
        parent::__construct();
    }

    private $updateStatusDelivery;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->updateStatusDelivery->processOrders();
        return Command::SUCCESS;
    }
}
