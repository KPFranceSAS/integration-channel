<?php

namespace App\Channels\Mirakl\MediaMarkt;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class MediaMarktIntegrator extends MiraklIntegratorParent
{
    
    public const MEDIAMARKT_ES = 'GI003333';
    
       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_MEDIAMARKT;
    }


    public function getCustomerBC($orderApi): string
    {
        return self::MEDIAMARKT_ES;
    }


    protected function getExternalNumber($orderApi)
    {
        return  $orderApi['id'];
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::GADGET_IBERIA;
    }



    public function transformToAnBcOrder($orderApi): SaleOrder
    {
        $orderBC = parent::transformToAnBcOrder($orderApi);
        $orderBC->sellingPostalAddress->countryLetterCode = 'ES';
        $orderBC->shippingPostalAddress->countryLetterCode = 'ES';
         
        return $orderBC;
    }

}
