<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Channels\ChannelAdvisor\ChannelAdvisorProduct;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportProductsCommand extends Command
{
    protected static $defaultName = 'app:channel-export-product';
    protected static $defaultDescription = 'Export product to Channel';

    public function __construct(ChannelAdvisorProduct $channelAdvisorProduct)
    {
        $this->channelAdvisorProduct = $channelAdvisorProduct;
        parent::__construct();
    }

    private $channelAdvisorProduct;


   
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->channelAdvisorProduct->syncProducts();
        return Command::SUCCESS;
    }
}
