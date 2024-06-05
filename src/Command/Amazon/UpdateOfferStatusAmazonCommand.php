<?php

namespace App\Command\Amazon;

use App\Service\Amazon\AmazonOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-amazon', 'Check status offers on Amazon')]
class UpdateOfferStatusAmazonCommand extends Command
{
    public function __construct(
        private readonly AmazonOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
