<?php

namespace App\Command\Channels\ChannelAdvisor;

use App\Channels\ChannelAdvisor\ChannelAdvisorApi;
use App\Channels\ChannelAdvisor\ChannelAdvisorIntegrateOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:channel-import-command', 'Import command')]
class ImportSpecificCommand extends Command
{
    public function __construct(private readonly ChannelAdvisorApi $channelAdvisorApi, private readonly ChannelAdvisorIntegrateOrder $channelAdvisorIntegrateOrder)
    {
        parent::__construct();
    }
   
    protected function configure(): void
    {
        $this
            
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
