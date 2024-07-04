<?php

namespace App\Command\Channels\Mirakl\Worten;

use App\Channels\Mirakl\Worten\WortenOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-worten', 'Check status on Leroy Merlin')]
class UpdateOfferStatusWortenCommand extends Command
{
    public function __construct(
        private readonly WortenOfferStatus $wortenAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->wortenAcceptOrder->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
