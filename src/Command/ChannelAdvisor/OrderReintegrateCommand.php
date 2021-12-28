<?php

namespace App\Command\ChannelAdvisor;

use App\Service\ChannelAdvisor\IntegrateOrdersChannelAdvisor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderReintegrateCommand extends Command
{
    protected static $defaultName = 'app:reintegrate-orders-from-channel';
    protected static $defaultDescription = 'Reintegrate all orders on error';

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
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->integrate->processOrders(true);

        return Command::SUCCESS;
    }
}
