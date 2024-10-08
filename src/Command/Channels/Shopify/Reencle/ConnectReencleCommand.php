<?php

namespace App\Command\Channels\Shopify\Reencle;

use App\Channels\Shopify\Reencle\ReencleApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:reencle-test', 'Connection to reencle test')]
class ConnectReencleCommand extends Command
{
    public function __construct(private readonly ReencleApi $reencleApi)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        dd($this->reencleApi->getAllOrders());


        return Command::SUCCESS;
    }
}
