<?php

namespace App\Command\ChannelAdvisor;

use App\Service\ChannelAdvisor\IntegrateOrdersChannelAdvisor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderIntegrateCommand extends Command
{
    protected static $defaultName = 'app:channel-integrate-orders-from-channel';
    protected static $defaultDescription = 'INtegrates all ChannelAdvisor orders waiting to be invoiced';

    public function __construct(IntegrateOrdersChannelAdvisor $integrate)
    {
        $this->integrate = $integrate;
        parent::__construct();
    }

    /**
     * 
     *
     * @var IntegrateOrdersChannelAdvisor
     */
    private $integrate;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('nbOrders', InputArgument::OPTIONAL, 'The number  of orders we want to be integrated');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $nbOrders = $input->getArgument('nbOrders');
        $this->integrate->processOrders(false, $nbOrders);
        return Command::SUCCESS;
    }
}
