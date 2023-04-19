<?php

namespace App\Command\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\Boulanger\BoulangerAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AcceptOrdersBoulangerCommand extends Command
{
    protected static $defaultName = 'app:accept-orders-boulanger';
    protected static $defaultDescription = 'Accept orders on Boulnager';

    public function __construct(
        BoulangerAcceptOrder $boulangerAcceptOrder
    ) {
        $this->boulangerAcceptOrder = $boulangerAcceptOrder;
        parent::__construct();
    }

    private $boulangerAcceptOrder;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->boulangerAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
