<?php

namespace App\Command\Channels\ManoMano\ManoManoDe;


use App\Channels\ManoMano\ManoManoDe\ManoManoDeOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-manomanode', 'Check status offers on manomanode')]
class UpdateOfferStatusManoManoDeCommand extends Command
{
    public function __construct(
        private readonly ManoManoDeOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
