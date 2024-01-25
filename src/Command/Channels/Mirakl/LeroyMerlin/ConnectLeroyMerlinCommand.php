<?php

namespace App\Command\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectLeroyMerlinCommand extends Command
{
    protected static $defaultName = 'app:connect-leroymerlin';
    protected static $defaultDescription = 'Connection to Leroy Merlin';

    public function __construct(
        private readonly LeroyMerlinApi $leroyMerlinApi
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        dd($this->leroyMerlinApi->getOffers());
       

        return Command::SUCCESS;
    }



}
