<?php

namespace App\Command;

use App\Service\ImportInvoice;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\Persistence\ManagerRegistry;

class InvoiceCommand extends Command
{
    protected static $defaultName = 'app:invoice-import';
    protected static $defaultDescription = 'Import all invoices';

    public function __construct(ImportInvoice $importInvoice){
       
        parent::__construct();
        $this->importInvoice=$importInvoice;

    }

    private $importInvoice;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {   
        $this->importInvoice->importFiles();
        return Command::SUCCESS;
    }
}


