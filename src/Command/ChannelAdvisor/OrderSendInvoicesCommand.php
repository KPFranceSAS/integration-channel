<?php

namespace App\Command\ChannelAdvisor;


use App\Service\ChannelAdvisor\SendInvoicesToChannelAdvisor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderSendInvoicesCommand extends Command
{
    protected static $defaultName = 'app:channel-send-invoices';
    protected static $defaultDescription = 'Send all orders transformed in invoices in the ERP';

    public function __construct(SendInvoicesToChannelAdvisor $integrate)
    {
        $this->integrate = $integrate;
        parent::__construct();
    }

    /**
     * 
     *
     * @var SendInvoicesToChannelAdvisor
     */
    private $integrate;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->integrate->processOrders();

        return Command::SUCCESS;
    }
}
