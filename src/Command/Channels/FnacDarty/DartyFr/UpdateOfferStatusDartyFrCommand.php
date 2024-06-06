<?php

namespace App\Command\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\DartyFr\DartyFrOfferStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:update-offer-status-dartyfr', 'Check status offers on dartyfr')]
class UpdateOfferStatusDartyFrCommand extends Command
{
    public function __construct(
        private readonly DartyFrOfferStatus $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->checkAllProducts();
       
        return Command::SUCCESS;
    }



}
