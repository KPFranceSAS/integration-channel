<?php

namespace App\Command\Channels\Mirakl\Worten;

use App\Channels\Mirakl\Worten\WortenAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-worten', 'Accept orders on Worten')]
class AcceptOrdersWortenCommand extends Command
{
    public function __construct(
        private readonly WortenAcceptOrder $wortenAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->wortenAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
