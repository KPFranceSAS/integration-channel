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

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-flashled', 'Connection to flashled')]
class FlashledConnectCommand extends Command
{
    public function __construct(private readonly FlashledApi $flashledApi)
    {
        parent::__construct();
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        dd($this->flashledApi->markAsFulfilled('5843965149527', 'SENDING', '999966449052', 'https://info.sending.es/fgts/pub/locNumSeguimiento.seam?web=S&localizador=999966449052')->getDecodedBody());
        return 1;
    }
}
