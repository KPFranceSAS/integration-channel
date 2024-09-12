<?php

namespace App\Command\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\CarrefourEs\CarrefourEsOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-carrefoures', 'Check status offers on CarrefourEs')]
class UpdateOfferStatusCarrefourEsCommand extends Command
{
    public function __construct(
        private readonly CarrefourEsOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
