<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\BusinessCentral\Connector\KpFranceConnector;
use App\Channels\Mirakl\Decathlon\DecathlonApi;
use App\Channels\Mirakl\Decathlon\DecathlonSyncProduct;
use Mirakl\MCI\Shop\Request\Hierarchy\GetHierarchiesRequest;
use Mirakl\MMP\Shop\Request\Channel\GetChannelsRequest;
use Mirakl\MMP\Shop\Request\Offer\GetAccountRequest;
use Mirakl\MMP\Shop\Request\Order\Get\GetOrdersRequest;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-decathlon', 'Connection to Deacthlon')]
class ConnectDecathlonCommand extends Command
{
    public function __construct(
        private readonly DecathlonApi $decathlonApi
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $imports = $this->decathlonApi->getLastOfferImports();
        foreach($imports as $import){
            if($import->getLinesInError()>0){
                $errosFiles= $this->decathlonApi->getReportErrorOffer($import->getImportId());
                $errors = [];
                foreach($errosFiles as $errosFile){
                    $errors[$errosFile['sku']]=$errosFile['error-message'];
                }
                dd($errors);
            } else {
                return [];
            }
        }
        
        return Command::SUCCESS;
    }





}
