<?php

namespace App\Command\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\CorteIngles\CorteInglesOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-corteingles', 'Check status on CorteIngles')]
class UpdateOfferStatusCorteInglesCommand extends Command
{
    public function __construct(
        private readonly CorteInglesOfferStatus $corteInglesAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->corteInglesAcceptOrder->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
