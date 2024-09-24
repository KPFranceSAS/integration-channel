<?php

namespace App\Channels\Mirakl\Decathlon;

use App\Channels\Mirakl\MiraklUploadAccountingDocumentParent;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;

class DecathlonUploadAccountingDocument extends MiraklUploadAccountingDocumentParent
{
    public function getChannel(): string
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }

   

    protected function getWebOrder(array $request) : ?WebOrder
    {

       $parties = explode("-",$request['entity_id']);
       return $this->manager->getRepository(WebOrder::class)->findOneBy([
            'channel'=>$this->getChannel(), 
            'externalNumber' => $parties[0],
            'erpDocument' => WebOrder::DOCUMENT_INVOICE
            ]
        );
    }

}
