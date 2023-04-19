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
        LeroyMerlinApi $leroyMerlinApi
    ) {
        $this->leroyMerlinApi = $leroyMerlinApi;
        parent::__construct();
    }

    private $leroyMerlinApi;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }



}
