<?php

namespace App\Command\Channels\ManoMano\ManoManoFr;


use App\Channels\ManoMano\ManoManoFr\ManoManoFrOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-manomanofr', 'Check status offers on manomanofr')]
class UpdateOfferStatusManoManoFrCommand extends Command
{
    public function __construct(
        private readonly ManoManoFrOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
