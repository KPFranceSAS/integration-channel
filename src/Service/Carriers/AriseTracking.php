<?php

namespace App\Service\Carriers;

use DateTime;
use Exception;
use GuzzleHttp\Client;

class AriseTracking
{

    public static function getGlsResponse($externalOrderNumber, $zipCode): ?array
    {
        try {
            $data = [
                "find"=> [
                    "reference" => $externalOrderNumber,
                    "destination" => [
                            "address" => [
                                "postalCode" => $zipCode,
                            ]
                        ]
                    ]
            ];

            $client = new Client();
            $response = $client->post(
                "https://api.consignee.gls-spain.es/api/v3/expeditions/find",
                [
                    'connect_timeout' => 1,
                    'json' => $data
                ]
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($body) {
                return $body['found'];
            }
        } catch (Exception $e) {
            error_log('DHL is not accessible '. $e->getMessage());
        }

        return null;
    }


    public static function checkIfDelivered($externalOrderNumber, $zipCode): ?DateTime
    {
        $body = self::getGlsResponse($externalOrderNumber,  $zipCode);
        if ($body 
            && array_key_exists('state', $body) 
            && array_key_exists('code', $body['state']) 
            && $body['state']['code'] == 'delivered' 
            && array_key_exists('deliveryDate', $body['state'])) {
            return DateTime::createFromFormat('Y-m-d', $body['state']['deliveryDate']);
        }
        return null;
    }





    public static function getTrackingUrlBase($trackingCode, $postCode)
    {
        return "https://mygls.gls-spain.es/e/".$trackingCode."/".$postCode."/en";
    }
}
