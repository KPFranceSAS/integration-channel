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

    public function __construct(ChannelAdvisorApi $channelAdvisorApi)
    {
        $this->channelAdvisorApi = $channelAdvisorApi;
        parent::__construct();
    }

    private $channelAdvisorApi;


   
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->channelAdvisorApi->markOrderAsFulfill('15380141', '1ZB5K5656822600309', 'https://www.ups.com/track?loc=en_IT&trackNums=1ZB5K5656822600309&requester=ST/trackdetails', 'UPS');
        return Command::SUCCESS;
    }
}
