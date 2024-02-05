<?php

namespace App\Command\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoFr\ManoManoFrAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-manomano-fr', 'Accept orders on Manomano Fr')]
class AcceptOrdersManoManoFrCommand extends Command
{
    public function __construct(
        private readonly ManoManoFrAcceptOrder $manoManoFrAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->manoManoFrAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
