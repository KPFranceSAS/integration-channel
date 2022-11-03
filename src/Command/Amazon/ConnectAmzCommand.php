<?php

namespace App\Command\Amazon;

use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiFinancial;
use App\Service\Amazon\AmzApiInbound;
use App\Service\Amazon\Report\AmzApiImportReimbursement;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAmzCommand extends Command
{
    protected static $defaultName = 'app:amz-test';
    protected static $defaultDescription = 'Add a short description for your command';

    public function __construct(AmzApi $api, ExchangeRateCalculator $caluclator, AmzApiInbound $amzApiInbound, AmzApiFinancial $financial)
    {
        $this->api = $api;
        $this->amzApiInbound = $amzApiInbound;
        $this->caluclator = $caluclator;
        $this->fincancial = $financial;
        parent::__construct();
    }

    private $api;

    private $fincancial;

    private $amzApiInbound;

    private $caluclator;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->amzApiInbound->sendInbounds();

        //dump($this->amzApiInbound->getLabels('FBA15GDH8SLH'));

        //$this->getFinancialEvents();
        //$this->getFinancialsGroup();

        return Command::SUCCESS;
    }




    protected function getProductData()
    {
        return $this->api->getProductData('B01N95Z86Y');
    }




    protected function getFinancialsGroup()
    {
        /*
    Select ROUND(SUM(afe.amount_currency)) as total,  ROUND(afeg.original_total_currency) as groupe,  afeg.id, afeg.start_date, afeg.end_date, afeg.financial_event_id , afeg.marketplace  FROM integration_channel.amazon_financial_event afe
    LEFT JOIN integration_channel.amazon_financial_event_group afeg on afeg.id = afe.event_group_id
    GROUP BY afeg.id

    Select SUM(afe.amount), SUM(afe.amount_currency), afe.transaction_type, afe.amount_type, afe.amount_description
FROM integration_channel.amazon_financial_event afe
WHERE event_group_id = 1
GROUP BY afe.transaction_type, afe.amount_type, afe.amount_description
    */

        $financialGroups = $this->fincancial->getAllFinancialEventsByGroup('XlFY-Vub4rWx9qpS4LgQeFC2MeRxqbKzTC7CmaREdIw');
        $sum = 0;
        foreach ($financialGroups as $financial) {
            $sum += $financial->getAmountCurrency();
        }
        dump($sum);



        /* $dateTime = new DateTime('2022-01-01');
        $dateTimeFin = new DateTime('2022-02-01');
        $financialGroups = $this->fincancial->getAllFinancials($dateTime, $dateTimeFin);*/
    }

    protected function getFinancialEvents()
    {
        $dateTime = new DateTime('2019-11-01');
        $dateTimeFin = new DateTime('2020-06-03');
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
