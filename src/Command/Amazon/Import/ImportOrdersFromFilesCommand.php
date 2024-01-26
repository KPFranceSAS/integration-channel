<?php

namespace App\Command\Amazon\Import;

use App\Helper\Utils\CsvExtracter;
use App\Service\Amazon\Report\AmzApiImportOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-import-orders-from-file', 'Import orders from file provided')]
class ImportOrdersFromFilesCommand extends Command
{
    public function __construct(private readonly AmzApiImportOrder $amzApiImportOrder, private readonly CsvExtracter $csvExtracter)
    {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->csvExtracter->extractAssociativeDatasFromCsv($input->getArgument('file'));
        $this->amzApiImportOrder->importDatas($orders);
        return Command::SUCCESS;
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('file', InputArgument::REQUIRED, 'Absolute path of file to import');
    }
}
