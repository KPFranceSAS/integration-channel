<?php

namespace App\Service\Carriers;

use DateTime;
use Exception;
use GuzzleHttp\Client;

class DhlGetTracking
{
    public static function getTrackingExternalWeb($externalOrderNumber): ?string
    {
        $body = self::getDHLResponse($externalOrderNumber);
        if ($body) {
            $toReplace = [];
            for ($i = 1; $i < 10; $i++) {
                $toReplace[]=" ".$i."0";
            }
            return str_replace($toReplace, "", $body['NumeroExpedicionTLG']);
        }
        return null;
    }


    public static function checkIfDelivered($externalOrderNumber): ?DateTime
    {
        $body = self::getDHLResponse($externalOrderNumber);
        if ($body && $body['FechaEntrega']) {
            return DateTime::createFromFormat('d/m/Y', $body['FechaEntrega']);
        }
        return null;
    }



    public static function getDHLResponse($externalOrderNumber): ?array
    {
        try {
            $client = new Client();
            $response = $client->get(
                'https://clientesparcel.dhl.es/LiveTracking/api/expediciones?numeroExpedicion=' . $externalOrderNumber,
                ['connect_timeout' => 1]
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($body) {
                return $body;
            }
        } catch (Exception $e) {
            error_log('DHL is not accessible');
        }

        return null;
    }




    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/" . $codeTracking;
    }
}
