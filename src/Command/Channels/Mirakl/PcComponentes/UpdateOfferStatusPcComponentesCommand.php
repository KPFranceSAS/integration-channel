<?php

namespace App\Command\Channels\Mirakl\PcComponentes;

use App\Channels\Mirakl\PcComponentes\PcComponentesOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-pccomponentes', 'Check status offers on PcComponentes')]
class UpdateOfferStatusPcComponentesCommand extends Command
{
    public function __construct(
        private readonly PcComponentesOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
