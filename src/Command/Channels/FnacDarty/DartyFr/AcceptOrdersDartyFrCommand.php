<?php

namespace App\Command\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\DartyFr\DartyFrAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-dartyfr', 'Accept orders on Darty Fr')]
class AcceptOrdersDartyFrCommand extends Command
{
    public function __construct(
        private readonly DartyFrAcceptOrder $dartyFrAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->dartyFrAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
