<?php

namespace App\Command\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinAcceptOrder;
use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-leroymerlin', 'Accept orders on Leroy Merlin')]
class AcceptOrdersLeroyMerlinCommand extends Command
{
    public function __construct(
        private readonly LeroyMerlinAcceptOrder $leroyMerlinAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->leroyMerlinAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
