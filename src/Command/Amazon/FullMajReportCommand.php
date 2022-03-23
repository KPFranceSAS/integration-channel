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

class FullMajReportCommand extends Command
{
    protected static $defaultName = 'app:amz-full-maj-report-updates';
    protected static $defaultDescription = 'Get datas from amz and export it to power bi';

    public function __construct(
        PublishPowerBi $publishPowerBi,
        AmzApiImportProduct $amzApiImportProduct,
        AmzApiImportOrder $amzApiImportOrder,
        AmzApiImportReturn $amzApiImportReturn,
        AmzApiImportReimbursement $amzApiImportReimbursement,
        AmzApiImportRemovalOrder $amzApiImportRemovalOrder
    ) {
        $this->amzApiImportProduct = $amzApiImportProduct;
        $this->amzApiImportOrder = $amzApiImportOrder;
        $this->amzApiImportReturn = $amzApiImportReturn;
        $this->amzApiImportReimbursement = $amzApiImportReimbursement;
        $this->amzApiImportRemovalOrder = $amzApiImportRemovalOrder;
        $this->publishPowerBi = $publishPowerBi;
        parent::__construct();
    }

    private $publishPowerBi;

    private $amzApiImportProduct;

    private $amzApiImportOrder;

    private $amzApiImportReturn;

    private $amzApiImportReimbursement;

    private $amzApiImportRemovalOrder;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImportProduct->createReportAndImport();
        $this->amzApiImportRemovalOrder->createReportAndImport();
        $this->amzApiImportOrder->createReportAndImport();
        $this->amzApiImportReturn->createReportAndImport();
        $this->amzApiImportReimbursement->createReportAndImport();
        $this->publishPowerBi->exportAll();
        return Command::SUCCESS;
    }
}
