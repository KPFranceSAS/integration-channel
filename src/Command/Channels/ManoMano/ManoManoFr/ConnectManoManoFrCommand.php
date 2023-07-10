<?php

namespace App\Command\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoFr\ManoManoFrApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectManoManoFrCommand extends Command
{
    protected static $defaultName = 'app:manomano-fr';
    protected static $defaultDescription = 'Connection to ManoMano FR';

    public function __construct(ManoManoFrApi $manoManoFrApi)
    {
        $this->manoManoFrApi = $manoManoFrApi;
        parent::__construct();
    }

    private $manoManoFrApi;


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dump(count($this->manoManoFrApi->getAllOrdersToSend()));
        
        return Command::SUCCESS;
    }
}
