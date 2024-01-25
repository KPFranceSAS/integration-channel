<?php

namespace App\Command\Channels\FnacDarty\DartyFr;

use App\Channels\FnacDarty\DartyFr\DartyFrApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectDartyFrCommand extends Command
{
    protected static $defaultName = 'app:connect-darty-fr';
    protected static $defaultDescription = 'Connection to DartyFr';

    public function __construct(private readonly DartyFrApi $dartyFrApi)
    {
        parent::__construct();
    }


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dd(str_replace("\n", '', (string) $this->dartyFrApi->getAllCarriers()));
        
        return Command::SUCCESS;
    }
}
