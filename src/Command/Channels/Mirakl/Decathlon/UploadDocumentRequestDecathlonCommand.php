<?php

namespace App\Command\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\Decathlon\DecathlonUploadAccountingDocument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:upload-document-requests-decathlon', 'Upload all requests for invoices')]
class UploadDocumentRequestDecathlonCommand extends Command
{
    public function __construct(
        private readonly DecathlonUploadAccountingDocument $offerStatus
    ) {
        parent::__construct();
    }

  

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        $this->offerStatus->uploadAllRequests();
       
        return Command::SUCCESS;
    }



}
