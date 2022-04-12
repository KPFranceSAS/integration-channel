<?php

namespace App\Command\FitbitExpress;

use App\Service\FitbitExpress\FitbitExpressApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectFitbitCommand extends Command
{
    protected static $defaultName = 'app:fitbitexpress-test';
    protected static $defaultDescription = 'Connection to fitbitexpress express';

    public function __construct(FitbitExpressApi $fitbitExpressApi)
    {
        $this->fitbitExpressApi = $fitbitExpressApi;
        parent::__construct();
    }

    private $fitbitExpressApi;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dump($this->fitbitExpressApi->getOrder('8135222170620281'));

        return Command::SUCCESS;
    }
}
