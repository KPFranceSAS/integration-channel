<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Channels\ChannelAdvisor\ChannelAdvisorApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestConnectCommand extends Command
{
    protected static $defaultName = 'app:channel-test-connect';
    protected static $defaultDescription = 'Test connect';

    public function __construct(private readonly ChannelAdvisorApi $channelAdvisorApi)
    {
        parent::__construct();
    }


   
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->channelAdvisorApi->markOrderAsNonExported('15380271');
        $this->channelAdvisorApi->markOrderAsNonExported('15380452');
        $this->channelAdvisorApi->markOrderAsNonExported('15380454');
        return Command::SUCCESS;
    }
}
