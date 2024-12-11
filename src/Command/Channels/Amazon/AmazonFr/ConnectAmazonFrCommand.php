<?php

namespace App\Command\Channels\Amazon\AmazonFr;

use App\Channels\Amazon\AmazonFr\AmazonFrApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amazon-fr', 'Connection to Amazon FR')]
class ConnectAmazonFrCommand extends Command
{
    public function __construct(private readonly AmazonFrApi $amazonFrApi)
    {
        parent::__construct();
    }


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //dd($this->amazonFrApi->getOrderItems("403-6990447-7745146"));
        
        //$this->amazonFrApi->getAllOrdersToInvoice()
        dump($this->amazonFrApi->getOrder("171-7524192-3193136"));
        //dd($this->amazonFrApi->getOrderItems("171-7524192-3193136"));
        return Command::SUCCESS;
    }
}
