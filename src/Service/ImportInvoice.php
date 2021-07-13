<?php

namespace App\Service;

use App\Entity\IntegrationFile;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Mailer\MailerInterface;
use App\Service\ChannelWebservice;
use Doctrine\Persistence\ManagerRegistry;


/**
 * Services that will send through the API all the datas
 * importFiles is the main method
 */
class ImportInvoice
{
    private $awsStorage;

    private $logger;

    private $channel;

    private $manager;

    private $dataInvoices = array();

    private $doublonsInvoices = array();


    /**
     * Constructor
     *
     * @param FilesystemOperator $awsStorage
     * @param ManagerRegistry $manager
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param ChannelWebservice $channel
     */
    public function __construct(FilesystemOperator $awsStorage, ManagerRegistry $manager, LoggerInterface $logger, MailerInterface $mailer, ChannelWebservice $channel)
    {
        $this->awsStorage=$awsStorage;
        $this->logger=$logger;
        $this->mailer=$mailer;
        $this->channel=$channel;
        $this->manager=$manager->getManager();
    }

    
    /**
     * 
     * 
     * @return void
     */
    public function importFiles()
    {
        try{
            $this->initializeDatas();
            $this->processInvoices();
            $this->sendEmailRapport();

        } catch (\Exception $e){
            $this->sendEmail('[VAT INVOICES] Error', $e->getMessage());

        }
        
    }


    /**
     * process all invocies directory
     *
     * @return void
     */
    protected function processInvoices(){
        
        $invoices = $this->awsStorage->listContents('invoices')->toArray();
        foreach($invoices as $invoice){
            if($invoice->isFile()){
                if($this->processInvoice($invoice->path())){
                    $this->nbFactures++;
                } 
                if($this->nbFactures == 1){
                    return;
                }
            }
        }
    }

    /**
     * Processes one invoice
     * 
     * @param string $path Ce path of the file
     * @return void
     */
    protected function processInvoice($path){
        $numberOrder=str_replace(['invoices/', '.pdf'], '', $path);
        $this->logger->info('Process invoice order '.$numberOrder);
        if(!array_key_exists($numberOrder, $this->dataInvoices)){
            if(!in_array($numberOrder, $this->doublonsInvoices) ){
                $this->addError('[NOT IN FILE] '.$numberOrder.'  not found in the details.csv files');
            }
            return false;
        }

        $invoiceCorrespondance = $this->dataInvoices[$numberOrder];

        if($this->checkIfAlreadyIntegrateInvoice($invoiceCorrespondance['external_order_id'])){
            $this->addError('[ALREADY INTEGRATED] '.$numberOrder.' already integrated on ChannelAdvisor');
            return false;
        }


        $orderChannelId=$this->channel->getOrderByNumber($invoiceCorrespondance['external_order_id'], $invoiceCorrespondance['ca_marketplace_id']);
        if(!$orderChannelId){
            $this->addError('[NOT FOUND] '.$numberOrder.' non found on ChannelAdvisor');
            return false;
        }

        $integrationFile = new IntegrationFile($invoiceCorrespondance);
        $integrationFile->setChannelOrderId($orderChannelId);       
        $dataFile = $this->awsStorage->read($path);
       
        $sendFile = $this->channel->sendInvoice($integrationFile->getProfileChannel(), $orderChannelId, $integrationFile->getTotalVatIncluded(), $integrationFile->getTotalVat(), $integrationFile->getDocumentNumber(), $dataFile);
        if($sendFile){
            $this->manager->persist($integrationFile);
            $this->awsStorage->move($path, $integrationFile->getNewFileDestination() );
            $this->manager->flush();
            return true;
        } else {
            $this->addError('[NOT SENT] '.$numberOrder.' was not send on ChannelAdvisor');
        }
        return false;
    }


    /**
     * Add to the erro array
     * Display Error log
     *
     * @param string $stringError
     * @return void
     */
    protected function addError($stringError){
        $this->errors[]=$stringError;
        $this->logger->error($stringError);
    }



    /**
     * Get all the dats from the CSV and create an associative array
     *
     * @return void
     */
    protected  function initializeDatas()
    {
        
            $this->debut=date('d-m-Y H:i');
            $this->initializeDatasFromCsv();   
            $this->nbFactures=0;
            $this->nbAvoirs=0;
            $this->errors = [];
    }




     /**
     *
     *  Get all the dats from the CSV and create an associative array
     *
     * @return boolean
     */
    public function initializeDatasFromCsv()
    {
        $this->logger->info("Get the details.csv file");
        $contentFile = $this->awsStorage->readStream('details.csv');
        $header = fgetcsv($contentFile, null, ';');
        while (($values = fgetcsv($contentFile, null, ';')) !== false) {
            if (count($values) == count($header)) {
                $dataInvoice = array_combine($header, $values);
                if($dataInvoice['document_type']== 'invoice'){
                    if(!array_key_exists ($dataInvoice['external_order_id'], $this->dataInvoices)){
                        $this->dataInvoices[$dataInvoice['external_order_id']]=$dataInvoice;
                    } else {
                        
                        $this->doublonsInvoices[]=$dataInvoice['external_order_id'];
                    }
                }
            }
        }


        foreach($this->doublonsInvoices as $doublonInvoice){
            unset($this->dataInvoices[$doublonInvoice]);
        }
        $this->logger->info('Nb of invoices :'.count($this->dataInvoices));
        $this->logger->info('Nb of duplicated invoices :'.count($this->doublonsInvoices));
        return $this->dataInvoices;
    }






    /**
     * Send rapport integrations
     *
     * @return void
     */
    protected function sendEmailRapport()
    {   
        $text='<p>Done between '.$this->debut.' and '.date('d-m-Y H:i:s').'</p>';
        $text.='<p>Integrated invoices : '.$this->nbFactures.'</p>';
        $text.='<p>Integrated credit notes '.$this->nbAvoirs.'</p>';
        $text.='<p>Errors <ul>';
        foreach($this->errors as $error){
            $text.='<li>'.$error.'</li>';
        }
        $text.='</ul></p>';
        $text.='<p>Errors of  duplicate order number in the details.csv files<ul>';
        foreach($this->doublonsInvoices as $doublonInvoice){
            $text.='<li>'.$doublonInvoice.'</li>';
        }
        $text.='</ul></p>';
        $this->sendEmail('[VAT INVOICES] Rapport', $text);
    }



    /**
     * Send an email
     *
     * @param string $titre tht title of the email
     * @param string $contenu the content of the email
     * @return void
     */
    protected function sendEmail($titre, $contenu)
    {   
        $this->logger->info("Sending email $titre  > $contenu");
        
        return;
        $email = (new Email())
        ->from('redmine.seriel@gmail.com')
        ->to('devops@kpsport.com')
        ->subject($titre)
        ->html($contenu);
        $this->mailer->send($email);
    }



    

    /**
     * Check in the database if already sent
     *
     * @param string $orderExternalId
     * @return boolean
     */
    private function checkIfAlreadyIntegrateInvoice($orderExternalId){
        $files = $this->manager->getRepository(IntegrationFile::class)->findBy(
            [
                'externalOrderId' => $orderExternalId, 
                'documentType' => IntegrationFile::TYPE_INVOICE
            ]
            );
        return count($files) > 0;    
    }




   
}
