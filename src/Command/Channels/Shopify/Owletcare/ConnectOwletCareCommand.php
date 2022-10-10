<?php

namespace App\Command\Channels\Shopify\Owletcare;

use App\Channels\Shopify\OwletCare\OwletCareApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectOwletCareCommand extends Command
{
    protected static $defaultName = 'app:owletcare-test';
    protected static $defaultDescription = 'Connection to owletcare test';

    public function __construct(OwletCareApi $owletCareApi)
    {
        $this->owletCareApi = $owletCareApi;
        parent::__construct();
    }


    private $owletCareApi;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
