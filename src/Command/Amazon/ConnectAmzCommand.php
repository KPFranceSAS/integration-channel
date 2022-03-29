<?php

namespace App\Command\Amazon;

use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiFinancial;
use App\Service\Amazon\Report\AmzApiImportReimbursement;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAmzCommand extends Command
{
    protected static $defaultName = 'app:amz-test';
    protected static $defaultDescription = 'Add a short description for your command';

    public function __construct(AmzApi $api, ExchangeRateCalculator $caluclator, AmzApiImportReimbursement $amzApiImportReimbursement, AmzApiFinancial $financial)
    {
        $this->api = $api;
        $this->amzApiImportReimbursement = $amzApiImportReimbursement;
        $this->caluclator = $caluclator;
        $this->fincancial = $financial;
        parent::__construct();
    }

    private $api;

    private $fincancial;

    private $amzApiImportReimbursement;

    private $caluclator;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {


        return Command::SUCCESS;
    }


    protected function getProductData()
    {
        return $this->api->getProductData('B01N95Z86Y');
    }




    protected function getFinancials()
    {
        $dateTime = new DateTime('2022-01-01');
        $dateTimeFin = new DateTime('2022-02-01');
        $financialGroups = $this->api->getAllFinancials($dateTime, $dateTimeFin);
    }

    protected function getFinancialEvents()
    {

        $dateTime = new DateTime('2022-01-01');
        $dateTimeFin = new DateTime('2022-02-01');
        $this->fincancial->getAllFinancials($dateTime, $dateTimeFin);
    }




    protected function calculate()
    {
        $tests = [
            [12.68, 'EUR', '2021-04-16',],
            [12.68, 'GBP', '2021-04-16',],
            [12.68, 'USD', '2021-04-16',],
            [250.68, 'EUR', '2022-01-01',],
            [250.68, 'GBP', '2022-01-01',],
            [250.68, 'USD', '2022-01-01',],
            [12.68, 'EUR', '2019-04-16',],
            [12.68, 'GBP', '2019-04-16',],
            [12.68, 'USD', '2019-04-16',],
            [250, 'EUR', '2022-01-06',],
            [250, 'GBP', '2022-01-06',],
            [250, 'USD', '2022-01-06',],
        ];


        foreach ($tests as $k => $test) {
            $tests[$k][] = $this->caluclator->getConvertedAmount($test[0], $test[1], $test[2]);
            $tests[$k][] = $this->caluclator->getRate($test[1], $test[2]);
        }
        dump($tests);

        return Command::SUCCESS;
    }
}
