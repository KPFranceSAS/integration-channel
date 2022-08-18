<?php

namespace App\Command\ChannelAdvisor;

use App\Service\ChannelAdvisor\ChannelAdvisorApi;
use App\Service\ChannelAdvisor\ChannelAdvisorIntegrateOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSpecificCommand extends Command
{
    protected static $defaultName = 'app:channel-import-command';
    protected static $defaultDescription = 'Import command';

    public function __construct(ChannelAdvisorApi $channelAdvisorApi, ChannelAdvisorIntegrateOrder $channelAdvisorIntegrateOrder)
    {
        $this->channelAdvisorApi = $channelAdvisorApi;
        $this->channelAdvisorIntegrateOrder= $channelAdvisorIntegrateOrder;
        parent::__construct();
    }

    private $channelAdvisorApi;

    private $channelAdvisorIntegrateOrder;
   
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
            $this->channelAdvisorIntegrateOrder->integrateOrder($orderApi);
        }
        
        return Command::SUCCESS;
    }
}
