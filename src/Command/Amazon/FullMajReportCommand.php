<?php

namespace App\Command\Amazon;

use App\Service\Amazon\Report\AmzApiImportOrder;
use App\Service\Amazon\Report\AmzApiImportProduct;
use App\Service\Amazon\Report\AmzApiImportReimbursement;
use App\Service\Amazon\Report\AmzApiImportRemovalOrder;
use App\Service\Amazon\Report\AmzApiImportReturn;
use App\Service\Amazon\Report\PublishPowerBi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-full-maj-report-updates', 'Get datas from amz and export it to power bi')]
class FullMajReportCommand extends Command
{
    public function __construct(
        private readonly PublishPowerBi $publishPowerBi,
        private readonly AmzApiImportProduct $amzApiImportProduct,
        private readonly AmzApiImportOrder $amzApiImportOrder,
        private readonly AmzApiImportReturn $amzApiImportReturn,
        private readonly AmzApiImportReimbursement $amzApiImportReimbursement,
        private readonly AmzApiImportRemovalOrder $amzApiImportRemovalOrder
    ) {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportProduct->updateProducts();
        $this->amzApiImportRemovalOrder->createReportAndImport();
        $this->amzApiImportOrder->createReportAndImport();
        $this->amzApiImportReturn->createReportAndImport();
        $this->amzApiImportReimbursement->createReportAndImport();
        //$this->publishPowerBi->exportAll();
        return Command::SUCCESS;
    }
}
