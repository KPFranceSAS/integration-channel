<?php

namespace App\Command\Channels\Mirakl\Boulanger;

use App\Channels\Mirakl\Boulanger\BoulangerOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-boulanger', 'Check status offers on boulanger')]
class UpdateOfferStatusBoulangerCommand extends Command
{
    public function __construct(
        private readonly BoulangerOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
