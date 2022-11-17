<?php

namespace App\Command\Channels\Shopify\Flashled;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Model\CustomerPayment;
use App\Channels\Shopify\Flashled\FlashledApi;
use App\Channels\Shopify\Flashled\FlashledIntegrateOrder;
use App\Helper\Utils\DatetimeUtils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportFlashledTransactionCommand extends Command
{
    protected static $defaultName = 'app:export-flashled';
    protected static $defaultDescription = 'Connection to owletcare test';

    public function __construct(FlashledApi $flashledApi, KitPersonalizacionSportConnector $kitPerzonalisationSport)
    {
        $this->flashledApi = $flashledApi;
        $this->kitPerzonalisationSport = $kitPerzonalisationSport;
        parent::__construct();
    }

    private $flashledApi;

    private $kitPerzonalisationSport;


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orders = $this->flashledApi->getAllOrders();
        $count = 0;

        $customerJournal = $this->kitPerzonalisationSport->getCustomerPaymentJournalByCode('SABADELL');


        foreach ($orders as $order) {
            if ($count<10) {
                $transactions = $this->flashledApi->getAllTransactions($order['id']);
                foreach ($transactions as $transaction) {
                    $customerPayment = $this->getCustomerPayment($transaction, $order);


                    if ($customerPayment) {
                        $result = $this->kitPerzonalisationSport->createCustomerPayment($customerJournal['id'], $customerPayment->transformToArray());
                    }
                }
            }
            $count ++;
        }


        return Command::SUCCESS;
    }


    private function getCustomerPayment($transaction, $order): ?CustomerPayment
    {
        if ($transaction['status']!='success' && $transaction['kind']!='sale') {
            return null;
        }

        $datePayment = DatetimeUtils::transformFromIso8601($transaction['processed_at']);

        $customerPayment = new CustomerPayment();
        $customerPayment->amount = floatval($transaction['amount']);
        $customerPayment->customerNumber = FlashledIntegrateOrder::FLASHLED_CUSTOMER_NUMBER;
        $customerPayment->postingDate = $datePayment->format('Y-m-d');
        $customerPayment->externalDocumentNumber = 'FLS-'.$order['order_number'];
        $customerPayment->comment = 'Payment gateway '.$transaction['gateway'];
        $customerPayment->description = 'Transaction '.$order['billing_address']['last_name'].' '.$order['billing_address']['first_name'] ;

        return $customerPayment;
    }
}
