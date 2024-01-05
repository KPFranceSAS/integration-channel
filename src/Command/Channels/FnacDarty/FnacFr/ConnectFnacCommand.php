<?php

namespace App\Command\Channels\FnacDarty\FnacFr;

use App\Channels\FnacDarty\FnacFr\FnacFrApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectFnacCommand extends Command
{
    protected static $defaultName = 'app:connect-fnac-fr';
    protected static $defaultDescription = 'Connection to Fnac';

    public function __construct(FnacFrApi $manoManoFrApi)
    {
        $this->manoManoFrApi = $manoManoFrApi;
        parent::__construct();
    }

    private $manoManoFrApi;


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dd($this->manoManoFrApi->getAllOffers());
        
        return Command::SUCCESS;
    }
}
