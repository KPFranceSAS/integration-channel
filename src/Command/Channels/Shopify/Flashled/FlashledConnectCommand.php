<?php

namespace App\Command\Channels\Shopify\Flashled;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Model\CustomerPayment;
use App\Channels\Shopify\Flashled\FlashledApi;
use App\Channels\Shopify\Flashled\FlashledIntegrateOrder;
use App\Helper\Utils\DatetimeUtils;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Date;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-flashled', 'Connection to flashled')]
class FlashledConnectCommand extends Command
{
    public function __construct(private readonly FlashledApi $flashledApi)
    {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $dateMin = new DateTime();
        $dateMin->sub(new DateInterval('P7D'));    

        $params = [
            'date_min' => $dateMin->format('Y-m-d'),
            'status' => "paid"
        ];


        //$payouts = $this->flashledApi->getPayouts($params);
        //dd($payouts);

        $transactions = $this->flashledApi->getAllShopifyPaiements(["payout_id"=>124437856599]);
        dd($transactions);


        //dd($this->flashledApi->getOrderById(6362788495703));
        
        

        return 1;
    }
}
