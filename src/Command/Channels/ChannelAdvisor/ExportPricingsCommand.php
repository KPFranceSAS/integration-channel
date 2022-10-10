<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Channels\ChannelAdvisor\ChannelAdvisorApi;
use App\Channels\ChannelAdvisor\ChannelAdvisorPricing;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportPricingsCommand extends Command
{
    protected static $defaultName = 'app:channel-export-pricings';
    protected static $defaultDescription = 'Export pricings';

    public function __construct(ChannelAdvisorPricing $channelAdvisorPricing)
    {
        $this->channelAdvisorPricing = $channelAdvisorPricing;
        parent::__construct();
    }

    private $channelAdvisorPricing;


   
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->channelAdvisorPricing->exportPricings();
        return Command::SUCCESS;
    }
}
