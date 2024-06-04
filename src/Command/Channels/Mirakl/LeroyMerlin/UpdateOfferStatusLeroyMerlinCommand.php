<?php

namespace App\Command\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-leroymerlin', 'Check status on Leroy Merlin')]
class UpdateOfferStatusLeroyMerlinCommand extends Command
{
    public function __construct(
        private readonly LeroyMerlinOfferStatus $leroyMerlinAcceptOrder
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->leroyMerlinAcceptOrder->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
