<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Channels\ChannelAdvisor\ChannelAdvisorApi;
use App\Channels\ChannelAdvisor\ChannelAdvisorIntegrateOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSpecificCommand extends Command
{
    protected static $defaultName = 'app:channel-import-command';
    protected static $defaultDescription = 'Import command';

    public function __construct(private readonly ChannelAdvisorApi $channelAdvisorApi, private readonly ChannelAdvisorIntegrateOrder $channelAdvisorIntegrateOrder)
    {
        parent::__construct();
    }
   
    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('orderNumber', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orderNumber = $input->getArgument('orderNumber');
        $orderApi = $this->channelAdvisorApi->getOrderByNumber($orderNumber);
        if ($orderApi) {
            $orderApi->ShippingStatus = 'Shipped';
            $this->channelAdvisorIntegrateOrder->integrateOrder($orderApi);
        }
        
        return Command::SUCCESS;
    }
}
