<?php

namespace App\Command\Channels\Mirakl\MediaMarkt;

use App\Channels\Mirakl\MediaMarkt\MediaMarktOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-mediamarkt', 'Check status offers on Mediamarkt')]
class UpdateOfferStatusMediaMarktCommand extends Command
{
    public function __construct(
        private readonly MediaMarktOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
