<?php

namespace App\Command\Channels\Mirakl\CorteIngles;

use App\Channels\Mirakl\CorteIngles\CorteInglesApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-corteingles', 'Connection to CorteIngles')]
class ConnectCorteInglesCommand extends Command
{
    public function __construct(
        private readonly CorteInglesApi $corteInglesApi
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        
        return Command::SUCCESS;
    }



}
