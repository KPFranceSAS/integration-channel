<?php

namespace App\Command\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\CorteIngles\CorteInglesAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-corteingles', 'Accept orders on CorteIngles')]
class AcceptOrdersCorteInglesCommand extends Command
{
    public function __construct(
        private readonly CorteInglesAcceptOrder $corteInglesAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->corteInglesAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
