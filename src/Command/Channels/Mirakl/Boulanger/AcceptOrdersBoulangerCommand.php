<?php

namespace App\Command\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\Boulanger\BoulangerAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-boulanger', 'Accept orders on Boulnager')]
class AcceptOrdersBoulangerCommand extends Command
{
    public function __construct(
        private readonly BoulangerAcceptOrder $boulangerAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->boulangerAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
