<?php

namespace App\Command\Amazon\Import;

use App\Helper\Utils\CsvExtracter;
use App\Service\Amazon\Report\AmzApiImportOrder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportOrdersFromFilesCommand extends Command
{
    protected static $defaultName = 'app:amz-import-orders-from-file';
    protected static $defaultDescription = 'Import orders from file provided';

    public function __construct(AmzApiImportOrder $amzApiImportOrder, CsvExtracter $csvExtracter)
    {
        $this->csvExtracter = $csvExtracter;
        $this->amzApiImportOrder = $amzApiImportOrder;
        parent::__construct();
    }

    private $amzApiImportOrder;

    private $csvExtracter;


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
