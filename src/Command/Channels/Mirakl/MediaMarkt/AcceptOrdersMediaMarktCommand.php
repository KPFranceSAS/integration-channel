<?php

namespace App\Command\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MediaMarkt\MediaMarktAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:accept-orders-mediamarkt', 'Accept orders on mediamarkt')]
class AcceptOrdersMediaMarktCommand extends Command
{
    public function __construct(
        private readonly MediaMarktAcceptOrder $mediaMarktAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->mediaMarktAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
