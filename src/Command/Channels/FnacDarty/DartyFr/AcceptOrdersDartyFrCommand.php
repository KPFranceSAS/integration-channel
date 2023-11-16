<?php

namespace App\Command\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\DartyFr\DartyFrAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AcceptOrdersDartyFrCommand extends Command
{
    protected static $defaultName = 'app:accept-orders-dartyfr';
    protected static $defaultDescription = 'Accept orders on Darty Fr';

    public function __construct(
        DartyFrAcceptOrder $dartyFrAcceptOrder
    ) {
        $this->dartyFrAcceptOrder = $dartyFrAcceptOrder;
        parent::__construct();
    }

    private $dartyFrAcceptOrder;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->dartyFrAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
