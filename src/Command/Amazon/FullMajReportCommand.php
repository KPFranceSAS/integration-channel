<?php

namespace App\Command\Amazon;


use App\Service\Amazon\AmzApiImport;
use App\Service\Amazon\PublishPowerBi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FullMajReportCommand extends Command
{
    protected static $defaultName = 'app:amz-full-maj-report-updates';
    protected static $defaultDescription = 'Get datas from amz and export it to power bi';

    public function __construct(PublishPowerBi $publishPowerBi, AmzApiImport $amzApiImport)
    {
        $this->amzApiImport = $amzApiImport;
        $this->publishPowerBi = $publishPowerBi;
        parent::__construct();
    }

    private $publishPowerBi;

    private $amzApiImport;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiImport->createReportOrdersAndImport();
        $this->publishPowerBi->exportAll();
        return Command::SUCCESS;
    }
}
