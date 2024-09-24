<?php

namespace App\Channels\Mirakl;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\Channels\Mirakl\MiraklApiParent;
use App\Entity\ProductSaleChannel;
use App\Entity\WebOrder;
use App\Helper\MailService;
use App\Helper\Traits\TraitServiceLog;
use App\Service\Aggregator\ApiAggregator;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

abstract class MiraklUploadAccountingDocumentParent
{
    /**@var EntityManager */
    protected $manager;

    use TraitServiceLog;


    public function __construct(
        ManagerRegistry $manager,
        protected LoggerInterface $logger,
        protected MailService $mailer,
        protected BusinessCentralAggregator $businessCentralAggregator,
        protected ApiAggregator $apiAggregator,

    ) {
        $this->manager = $manager->getManager();
    }

    abstract public function getChannel(): string;


    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->apiAggregator->getApi($this->getChannel());
    }


    public function uploadAllRequests()
    {
        $documentsRequests = $this->getMiraklApi()->getAccountingDocumentRequests('INVOICE', 'TO_PROCESS', 'PRODUCT_LOGISTIC_ORDER');

        foreach($documentsRequests as $documentRequest){
            $this->uploadRequest($documentRequest);
        }


        
        
    }

    protected function uploadRequest(array $request)
    {
        $this->logger->info('Check request id '.$request["entity_id"]);
        $order = $this->getWebOrder($request);
        if($order){
            $businessCentralConnector   = $this->businessCentralAggregator->getBusinessCentralConnector($order->getCompany());
            $this->addLogToOrder($order, 'Retrieve invoice content ' . $order->getInvoiceErp());
            $invoice = $businessCentralConnector->getSaleInvoiceByNumber($order->getInvoiceErp());
            $contentPdf  = $businessCentralConnector->getContentInvoicePdf($invoice['id']);
            $this->addLogToOrder($order, 'Retrieved invoice content ' . $order->getInvoiceErp());
                
            
            $result = $this->getMiraklApi()->uploadAccountingDocument($request, $invoice, $contentPdf);
            $this->addLogToOrder($order, 'Request '.$request['id']. ' adding Invoice ' . $invoice['number'].' uploaded on '.$this->getChannel());
            $this->manager->flush();
        } else {
            $this->logger->alert('Not found Weborder');
        }
        
        
    }



    protected function getWebOrder(array $request) : ?WebOrder
    {
       return $this->manager->getRepository(WebOrder::class)->findOneBy([
            'channel'=>$this->getChannel(), 
            'externalNumber' => $request['entity_id'],
            'erpDocument' => WebOrder::DOCUMENT_INVOICE
            ]
        );
    }


       

    




}
