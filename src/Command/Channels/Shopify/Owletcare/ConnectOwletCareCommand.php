<?php

namespace App\Command\Channels\Shopify\Owletcare;

use App\Channels\Shopify\OwletCare\OwletCareApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:owletcare-test', 'Connection to owletcare test')]
class ConnectOwletCareCommand extends Command
{
    public function __construct(private readonly OwletCareApi $owletCareApi)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
