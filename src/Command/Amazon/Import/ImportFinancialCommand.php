<?php

namespace App\Command\Amazon\Import;

use App\Service\Amazon\AmzApiFinancial;
use DateInterval;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportFinancialCommand extends Command
{
    protected static $defaultName = 'app:amz-import-financial';
    protected static $defaultDescription = 'Import financial events';

    public function __construct(AmzApiFinancial $amzApiFinancial)
    {
        $this->amzApiFinancial = $amzApiFinancial;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('startDate', InputArgument::OPTIONAL, 'Start date Ymd')
            ->addArgument('endDate', InputArgument::OPTIONAL, 'End date Ymd');
    }


    private $amzApiFinancial;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getArgument('startDate')) {
            $startDate = DateTime::createFromFormat('Ymd', $input->getArgument('startDate'));
        } else {
            $startDate = new DateTime('now');
            $startDate->sub(new DateInterval("P15D"));
        }


        if ($input->getArgument('endDate')) {
            $endDate = DateTime::createFromFormat('Ymd', $input->getArgument('endDate'));
        } else {
            $endDate = new DateTime('now');
            $endDate->sub(new DateInterval("PT8H"));
        }

        $output->writeln('Import ' . $startDate->format('d-m-Y') . " to " . $endDate->format('d-m-Y'));

        $this->amzApiFinancial->getAllFinancials($startDate, $endDate);
        return Command::SUCCESS;
    }
}
