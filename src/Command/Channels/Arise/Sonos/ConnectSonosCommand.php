<?php

namespace App\Command\Channels\Arise\Sonos;

use App\Channels\Arise\Sonos\SonosApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectSonosCommand extends Command
{
    protected static $defaultName = 'app:arise-sonos-test';
    protected static $defaultDescription = 'Connection to Sonos amazfit';

    public function __construct(SonosApi $ariseApi)
    {
        $this->ariseApi = $ariseApi;
        parent::__construct();
    }

    private $ariseApi;

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->desactivateProduct();
        return Command::SUCCESS;
    }

    

    private function desactivateProduct()
    {
        $result =$this->ariseApi->desactivateProduct(1355784635873743, "SNS-ARCG1EU1");
        var_dump($result);
    }
}
