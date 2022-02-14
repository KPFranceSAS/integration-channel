<?php

namespace App\Command\Amazon;


use App\Service\Amazon\AmzApiImportOrder;
use App\Service\Amazon\AmzApiImportReimbursement;
use App\Service\Amazon\AmzApiImportReturn;
use App\Service\Amazon\PublishPowerBi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FullMajReportCommand extends Command
{
    protected static $defaultName = 'app:amz-full-maj-report-updates';
    protected static $defaultDescription = 'Get datas from amz and export it to power bi';

    public function __construct(PublishPowerBi $publishPowerBi, AmzApiImportOrder $amzApiImportOrder, AmzApiImportReturn $amzApiImportReturn, AmzApiImportReimbursement $amzApiImportReimbursement)
    {
        $this->amzApiImportOrder = $amzApiImportOrder;
        $this->amzApiImportReturn = $amzApiImportReturn;
        $this->amzApiImportReimbursement = $amzApiImportReimbursement;
        $this->publishPowerBi = $publishPowerBi;
        parent::__construct();
    }

    private $publishPowerBi;

    private $amzApiImportOrder;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportOrder->createReportAndImport();
        $this->amzApiImportReturn->createReportAndImport();
        $this->amzApiImportReimbursement->createReportAndImport();
        $this->publishPowerBi->exportAll();
        return Command::SUCCESS;
    }
}
