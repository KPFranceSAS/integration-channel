<?php

namespace App\Command\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MediaMarkt\MediaMarktAcceptOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AcceptOrdersMediaMarktCommand extends Command
{
    protected static $defaultName = 'app:accept-orders-mediamarkt';
    protected static $defaultDescription = 'Accept orders on mediamarkt';

    public function __construct(
        MediaMarktAcceptOrder $mediaMarktAcceptOrder
    ) {
        $this->mediaMarktAcceptOrder = $mediaMarktAcceptOrder;
        parent::__construct();
    }

    private $mediaMarktAcceptOrder;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->mediaMarktAcceptOrder->acceptAllOrders();
       
        return Command::SUCCESS;
    }



}
