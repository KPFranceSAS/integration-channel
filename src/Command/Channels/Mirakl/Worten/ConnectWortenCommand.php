<?php

namespace App\Command\Channels\Mirakl\Worten;

use App\Channels\Mirakl\Worten\WortenApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:connect-worten', 'Connection to Worten')]
class ConnectWortenCommand extends Command
{
    public function __construct(
        private readonly WortenApi $wortenApi
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $imports = $this->wortenApi->getLastOfferImports();
        foreach($imports as $import){
            if($import->getLinesInError()>0){
                $errosFiles= $this->wortenApi->getReportErrorOffer($import->getImportId());
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
