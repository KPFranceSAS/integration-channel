<?php

namespace App\Command\Channels\ManoMano\ManoManoDe;

use App\Channels\ManoMano\ManoManoDe\ManoManoDeAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-manomano-de', 'Accept orders on Manomano De')]
class AcceptOrdersManoManoDeCommand extends Command
{
    public function __construct(
        private readonly ManoManoDeAcceptOrder $manoManoDeAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->manoManoDeAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
