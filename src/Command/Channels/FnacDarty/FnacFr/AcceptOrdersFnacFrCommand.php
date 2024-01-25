<?php

namespace App\Command\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacFr\FnacFrAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AcceptOrdersFnacFrCommand extends Command
{
    protected static $defaultName = 'app:accept-orders-fnacfr';
    protected static $defaultDescription = 'Accept orders on Fnac Fr';

    public function __construct(
        private readonly FnacFrAcceptOrder $fnacFrAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->fnacFrAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
