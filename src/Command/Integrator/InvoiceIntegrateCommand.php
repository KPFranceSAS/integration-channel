<?php

namespace App\Command\Integrator;

use App\Service\Integrator\IntegratorAggregator;
use App\Service\Invoice\InvoiceAggregator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InvoiceIntegrateCommand extends Command
{
    protected static $defaultName = 'app:integrate-invoices-from';
    protected static $defaultDescription = 'Integrates all invoices waiting to be transformed to invoices with the given sale channel';

    public function __construct(InvoiceAggregator $invoiceAggregator)
    {
        $this->invoiceAggregator = $invoiceAggregator;
        parent::__construct();
    }

    private $invoiceAggregator;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('channelIntegration', InputArgument::REQUIRED, 'Channel integration')
            ->addArgument('retryIntegration', InputArgument::OPTIONAL, 'To reimport all invoices add 1', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channelIntegration = strtoupper($input->getArgument('channelIntegration'));

        $integrator = $this->invoiceAggregator->getInvoice($channelIntegration);
        $retryIntegration = boolval($input->getArgument('retryIntegration'));
        $integrator->processInvoices($retryIntegration);
        return Command::SUCCESS;
    }
}
