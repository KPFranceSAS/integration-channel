<?php

namespace App\Command\Channels\ManoMano\ManoManoFr;

use App\Channels\ManoMano\ManoManoFr\ManoManoFrApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:manomano-fr', 'Connection to ManoMano FR')]
class ConnectManoManoFrCommand extends Command
{
    public function __construct(private readonly ManoManoFrApi $manoManoFrApi)
    {
        parent::__construct();
    }


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->manoManoFrApi->markOrderAsFulfill('M230963938680', 'DBSchenker', 'DBSchenker', 'https://www.dbschenker.com/app/tracking-public/?refNumber=ALVF23%2F015262&refType=ShippersRefNo', 'ALVF23/015262');
        
        return Command::SUCCESS;
    }
}
