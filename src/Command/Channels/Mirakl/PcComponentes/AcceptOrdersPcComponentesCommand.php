<?php

namespace App\Command\Channels\Mirakl\PcComponentes;

use App\Channels\Mirakl\PcComponentes\PcComponentesAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-pccomponentes', 'Accept orders on PCComponentes')]
class AcceptOrdersPcComponentesCommand extends Command
{
    public function __construct(
        private readonly PcComponentesAcceptOrder $pcComponentesAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->pcComponentesAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
