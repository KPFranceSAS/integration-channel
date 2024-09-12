<?php

namespace App\Command\Channels\Mirakl\CarrefourEs;

use App\Channels\Mirakl\CarrefourEs\CarrefourEsApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-carrefour', 'Connection to Leroy Merlin')]
class ConnectCarrefourEsCommand extends Command
{
    public function __construct(
        private readonly CarrefourEsApi $api
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dd($this->api->getOrders());
        
        return Command::SUCCESS;
    }



}
