<?php

namespace App\Command\Channels\Amazon\AmazonFr;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\Channels\Amazon\AmazonFr\AmazonFrApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:amazon-fr', 'Connection to Amazon FR')]
class ConnectAmazonFrCommand extends Command
{
    public function __construct(private readonly AmazonFrApi $amazonFrApi, private KpFranceConnector $kpFranceConnector)
    {
        parent::__construct();
    }


  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //dd($this->amazonFrApi->getOrderItems("403-6990447-7745146"));
        
        //$this->amazonFrApi->getAllOrdersToInvoice()
        //dump($this->amazonFrApi->getOrder("171-7524192-3193136"));

        /*$orderId ='408-6921837-5178765';
        $orderApi = $this->amazonFrApi->getOrder($orderId);
        $orderApi['Lines'] = $this->amazonFrApi->getOrderItems($orderId);
        $this->amazonFrApi->markOrderAsFulfill($orderId, $orderApi, 'Sending', 'Sending', 'ENTRAGA','999977122632');*/

        $orderId ='403-1872765-6713944';
        $invoice = $this->kpFranceConnector->getSaleInvoiceByNumber('FVF24/1200445');
        $contentPdf  =  $this->kpFranceConnector->getContentInvoicePdf($invoice['id']);
        $this->amazonFrApi->sendInvoice($orderId, $invoice['totalAmountIncludingTax'], $invoice['totalTaxAmount'], $invoice['number'], $contentPdf);
        



        return Command::SUCCESS;
    }
}
