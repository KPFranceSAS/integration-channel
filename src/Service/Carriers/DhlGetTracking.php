<?php

namespace App\Service\Carriers;

use DateTime;
use Exception;
use GuzzleHttp\Client;

class DhlGetTracking
{

    public const MAX_B2C = 30;

    public static function getTrackingExternalWeb($externalOrderNumber): ?string
    {
        $body = self::getDHLResponse($externalOrderNumber);
        if ($body) {
            $toReplace = [];
            for ($i = 1; $i < 10; $i++) {
                $toReplace[]=" ".$i."0";
            }
            return str_replace($toReplace, "", (string) $body['NumeroExpedicionTLG']);
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
                ['connect_timeout' => 5]
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($body) {
                return $body;
            }
        } catch (Exception $e) {
            error_log('DHL is not accessible '.$e->getMessage());
        }

        return null;
    }



    public static function getStepsTrackings($externalOrderNumber): ?array
    {
        $body = self::getDHLResponse($externalOrderNumber);
        if ($body) {
            $steps = [];
            foreach($body['Seguimiento'] as $step) {
                $dateEvent = DateTime::createFromFormat('d/m/Y H:i', $step['Fecha']. ' '.$step['Hora']);
                $description = $step['Descripcion']. ' '.$step['Ciudad'];
                $steps[ $dateEvent->format('YmdHis')]=[
                        'date'=>$dateEvent,
                        'description'=>$description
                ];
            }
            return $steps;
           
        }
        return null;
    }







    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://clientesparcel.dhl.es/LiveTracking/ModificarEnvio/" . $codeTracking;
    }
}
