<?php

namespace App\Channels\Mirakl\Decathlon;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Channels\Mirakl\MiraklIntegratorParent;
use App\Entity\IntegrationChannel;
use Exception;

class DecathlonIntegrator extends MiraklIntegratorParent
{
    public const DECATHLON_FR = '000774';
    public const DECATHLON_DE = '000777';
    public const DECATHLON_IT = '000778';
    public const DECATHLON_PT = '000779';
    public const DECATHLON_BE = '000780';
    public const DECATHLON_ES = '000786';

       

    public function getChannel()
    {
        return IntegrationChannel::CHANNEL_DECATHLON;
    }


    public function getCustomerBC($orderApi): string
    {
        $codeChannel = $orderApi['channel']['code'];
        if ($codeChannel == 'FR') {
            return self::DECATHLON_FR;
        } elseif ($codeChannel == 'DE') {
            return self::DECATHLON_DE;
        } elseif ($codeChannel == 'IT') {
            return self::DECATHLON_IT;
        } elseif ($codeChannel == 'PT') {
            return self::DECATHLON_PT;
        } elseif ($codeChannel == 'BE') {
            return self::DECATHLON_BE;
        } elseif ($codeChannel == 'ES') {
            return self::DECATHLON_ES;
        } else {
            throw new Exception('No customer has been setup for Decathlon on channel '.$codeChannel);
        }
    }


    protected function getExternalNumber($orderApi)
    {
        foreach ($orderApi['order_additional_fields'] as $field) {
            if ($field['code']=='orderid') {
                return $field['value'];
            }
        }
        return  null;
    }


    public function getCompanyIntegration($orderApi): string
    {
        return BusinessCentralConnector::KP_FRANCE;
    }
}
