<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\Decathlon\DecathlonOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-decathlon', 'Check status offers on decathlon')]
class UpdateOfferStatusDecathlonCommand extends Command
{
    public function __construct(
        private readonly DecathlonOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
