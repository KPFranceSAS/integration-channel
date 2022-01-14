<?php

namespace App\Command\Amazon;

use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConnectAmzCommand extends Command
{
    protected static $defaultName = 'app:amz-test';
    protected static $defaultDescription = 'Add a short description for your command';

    public function __construct(AmzApi $api, ExchangeRateCalculator $caluclator)
    {
        $this->api = $api;
        $this->caluclator = $caluclator;
        parent::__construct();
    }

    private $api;

    private $caluclator;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        //$this->calculate();

        //$this->createReport();
        //$this->readReport();

        //B01N95Z86Y

        //dump($this->getProductData());

        return Command::SUCCESS;
    }


    protected function getProductData()
    {
        return $this->api->getProductData('B01N95Z86Y');
    }




    protected function getFinancial()
    {
        $this->api->getAllFinancials();
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
        $report = $this->api->getContentLastReport(AmzApi::TYPE_REPORT_INVENTORY_DATA);
        dump($report);




        //$report = $this->api->getContentLastReportArchivedOrdersByLastUpdate();

        /*
        $orders = $this->api->getContentReport("amzn1.spdoc.1.3.3cb6f415-5013-431b-b8d3-416881a12ddb.T10Z1UVHS2BQSQ.2407");
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
