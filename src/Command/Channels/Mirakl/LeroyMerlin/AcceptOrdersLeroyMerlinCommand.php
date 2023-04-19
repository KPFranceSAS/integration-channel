<?php

namespace App\Command\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinAcceptOrder;
use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AcceptOrdersLeroyMerlinCommand extends Command
{
    protected static $defaultName = 'app:accept-orders-leroymerlin';
    protected static $defaultDescription = 'Accept orders on Leroy Merlin';

    public function __construct(
        LeroyMerlinAcceptOrder $leroyMerlinAcceptOrder,
    ) {
        $this->leroyMerlinAcceptOrder = $leroyMerlinAcceptOrder;
        parent::__construct();
    }

    private $leroyMerlinAcceptOrder;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->leroyMerlinAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
