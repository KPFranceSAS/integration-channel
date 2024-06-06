<?php

namespace App\Command\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacFr\FnacFrOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-fnacfr', 'Check status offers on fnacfr')]
class UpdateOfferStatusFnacFrCommand extends Command
{
    public function __construct(
        private readonly FnacFrOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
