<?php

namespace App\Command\Amazon;

use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiFinancial;
use App\Service\Amazon\AmzApiImportReimbursement;
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
        $dateTime = new DateTime('2022-01-01');
        $dateTimeFin = new DateTime('2022-02-01');
        $this->fincancial->getAllFinancials($dateTime, $dateTimeFin);
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
        dump($financialGroups);
    }

    protected function getFinancialEvents()
    {

        $this->fincancial->saveFinancialEvent('NuRxNbWRYUiGQ1hdkAkVAVwjG0khSelzEx6m5xr6nsA');
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



    private function readReport()
    {
        $datasReport = $this->api->getContentReport("amzn1.spdoc.1.3.69031a61-9c04-40ab-b314-a50d9fdc25c3.T2CFHLJ4Q6RCRF.2617");
        $this->amzApiImportReimbursement->importDatas($datasReport);




        /*
        $report = $this->api->getContentLastReport(AmzApi::TYPE_REPORT_INVENTORY_DATA);
        dump($report);
        */



        //$report = $this->api->getContentLastReportArchivedOrdersByLastUpdate();

        /*
        $orders = $this->api->getContentReport("amzn1.spdoc.1.3.69031a61-9c04-40ab-b314-a50d9fdc25c3.T2CFHLJ4Q6RCRF.2617");
        foreach ($orders as $order) {
            dump($order);
            /*   "amazon-order-id" => "403-1353844-3288348"
                "merchant-order-id" => "403-1353844-3288348"
                "purchase-date" => "2021-12-12T11:30:06+00:00"
                "last-updated-date" => "2021-12-12T12:02:16+00:00"
                "order-status" => "Cancelled"
                "fulfillment-channel" => "Amazon"
                "sales-channel" => "Amazon.it"
                "order-channel" => ""
                "ship-service-level" => "Expedited"
                "product-name" => "roborock X-S5E52-00 Robot aspirapolvere, policarbonato"
                "sku" => "X-S5E52-00"
                "asin" => "B088WC24MD"
                "number-of-items" => ""
                "item-status" => "Cancelled"
                "quantity" => "0"
                "currency" => ""
                "item-price" => ""
                "item-tax" => ""
                "shipping-price" => ""
                "shipping-tax" => ""
                "gift-wrap-price" => ""
                "gift-wrap-tax" => ""
                "item-promotion-discount" => ""
                "ship-promotion-discount" => ""
                "ship-city" => "bagheria"
                "ship-state" => "palermo"
                "ship-postal-code" => "90011"
                "ship-country" => "IT"
                "promotion-ids" => ""
                "fulfilled-by" => ""
                "buyer-company-name" => ""
                "is-amazon-invoiced" => "false"
                "vat-exclusive-item-price" => "0.00"
                "vat-exclusive-shipping-price" => "0.00"
                "vat-exclusive-giftwrap-price" => ""
        }
        */
    }


    private function createReport()
    {
        /*
        $report = $this->api->createReportOrdersByLastUpdate(new \DateTime('2021-12-12'));
        dump($report);
        */


        $report = $this->api->createReport(new \DateTime('now'), AmzApi::TYPE_REPORT_INVENTORY_DATA);
        dump($report);
    }
}
