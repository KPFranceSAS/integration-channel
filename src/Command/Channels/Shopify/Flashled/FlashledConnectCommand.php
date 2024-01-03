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

class FlashledConnectCommand extends Command
{
    protected static $defaultName = 'app:connect-flashled';
    protected static $defaultDescription = 'Connection to flashled';

    public function __construct(FlashledApi $flashledApi)
    {
        $this->flashledApi = $flashledApi;
        parent::__construct();
    }

    private $flashledApi;



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dd($this->flashledApi->getFulfilmentsFulfilmentOrder('5840272654679'));
        return 1;
    }
}
