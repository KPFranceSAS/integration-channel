<?php

namespace App\Command\Amazon;

use App\Helper\Utils\ExchangeRateCalculator;
use App\Service\Amazon\AmzApi;
use App\Service\Amazon\AmzApiFinancial;
use App\Service\Amazon\AmzApiInbound;
use App\Service\Amazon\Report\AmzApiImportReimbursement;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amz-test', 'Add a short description for your command')]
class ConnectAmzCommand extends Command
{
    public function __construct(private readonly AmzApi $api, private readonly ExchangeRateCalculator $caluclator, private readonly AmzApiInbound $amzApiInbound, private readonly AmzApiFinancial $fincancial)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        

        $shipments = $this->api->getShipmentSent();
        $progressBar = new ProgressBar($output, count($shipments));
        $progressBar->start();
        $results = [];
        foreach ($shipments as $shipment) {
            $progressBar->advance();
            $response = $this->api->getShipmentItems($shipment->getShipmentId());
            sleep(5);
            foreach ($response as $item) {
                $results[]= [
                            $shipment->getShipmentId(),
                            $shipment->getShipmentName(),
                            $item->getSellerSku(),
                            $item->getFulfillmentNetworkSKU(),
                            $item->getQuantityShipped(),
                            $item->getQuantityReceived(),
                            $item->getQuantityInCase(),
                        ];
            }

            
        }

        $progressBar->finish();


        $fileName = 'shipments_to_fba.csv';
        $file = fopen($fileName, 'w');

        // Ajouter les en-têtes du CSV
        fputcsv($file, ['ShipmentId', 'Shipmentname', 'SellerSKU', 'FulfillmentNetworkSKU', 'QuantityShipped', 'QuantityReceived', 'QuantityInCase']);

        // Ajouter les données dans le fichier CSV
        foreach ($results as $result) {
            fputcsv($file, array_values($result));
        }

        // Fermer le fichier CSV
        fclose($file);


        return Command::SUCCESS;
    }




    protected function getAmazonExpedition()
    {
        return dump($this->api->getShipmentReceived());
        
        return $this->api->getParcelShipments('S02-1485397-8719750');
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
        $dateTime = new DateTime('2023-11-01');
        $dateTimeFin = new DateTime('2023-11-03');
        $this->fincancial->getAllFinancials($dateTime, $dateTimeFin);
    }
}
