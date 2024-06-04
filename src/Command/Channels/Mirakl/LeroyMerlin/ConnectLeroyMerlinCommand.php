<?php

namespace App\Command\Channels\Mirakl\LeroyMerlin;

use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-leroymerlin', 'Connection to Leroy Merlin')]
class ConnectLeroyMerlinCommand extends Command
{
    public function __construct(
        private readonly LeroyMerlinApi $leroyMerlinApi
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $imports = $this->leroyMerlinApi->getLastOfferImports();
        foreach($imports as $import){
            if($import->getLinesInError()>0){
                $errosFiles= $this->leroyMerlinApi->getReportErrorOffer($import->getImportId());
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
