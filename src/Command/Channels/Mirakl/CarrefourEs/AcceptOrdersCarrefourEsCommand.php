<?php

namespace App\Command\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\CarrefourEs\CarrefourEsAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-carrefoures', 'Accept orders on mediamarkt')]
class AcceptOrdersCarrefourEsCommand extends Command
{
    public function __construct(
        private readonly CarrefourEsAcceptOrder $carrefourEsAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->carrefourEsAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
