<?php

namespace App\Service\Carriers;

use DateTime;
use Exception;
use GuzzleHttp\Client;

class DpdUkTracking
{


    final public const BASE_URL = 'https://apis.track.dpd.co.uk/v1/';

    final public const BASE_PARAMS = [
        'connect_timeout' => 1
    ];


    public static function getParcelCode($externalOrderNumber, $zipCode): ?string
    {
        try {
            $client = new Client();
            $response = $client->get(
                self::BASE_URL."/reference?postcode=$zipCode&referenceNumber=$externalOrderNumber", 
                self::BASE_PARAMS
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($body && array_key_exists('data', $body)) {
                return $body['data'][0]['parcelCode'];
            }
        } catch (Exception $e) {
            error_log('DPD CO UK is not accessible '. $e->getMessage());
        }

        return null;
    }


    public static function checkIfDelivered($externalOrderNumber, $zipCode): ?DateTime
    {
        $parcelEvents= self::getTrackingEvents($externalOrderNumber, $zipCode);
        if($parcelEvents) {
                foreach($parcelEvents as $parcelEvent) {
                    if($parcelEvent['eventCode'] =="001") {
                        return true;
                    }
                }
        }
        return null;
    }


    public static function getTrackingEvents($externalOrderNumber, $zipCode): ?array
    {

        $parcelNumber= self::getParcelCode($externalOrderNumber, $zipCode);
        
        try {
            if($parcelNumber) {
                $client = new Client();
                $response = $client->get(
                    self::BASE_URL."/parcels/$parcelNumber/parcelevents", 
                    self::BASE_PARAMS
                );
                $body = json_decode((string) $response->getBody(), true);
                if ($body && array_key_exists('data', $body)) {
                    return $body['data'];
                }
            }
        } catch (Exception $e) {
            error_log('DPD CO UK is not accessible '. $e->getMessage());
        }
        return null;
    }



    public static function getStepsTrackings($externalOrderNumber, $zipCode): ?array
    {
        $parcelEvents= self::getTrackingEvents($externalOrderNumber, $zipCode);
        if($parcelEvents) {
                $steps = [];
                foreach($parcelEvents as $parcelEvent) {
                    $dateEvent = DateTime::createFromFormat('Y-m-d H:i:s', $parcelEvent['eventDate']);
                    $steps[ $dateEvent->format('YmdHis')]=[
                            'date'=>$dateEvent,
                            'description'=>$parcelEvent['eventText']
                    ];
                }
                return $steps;
        }
        return null;
    }

    public static function getTrackingUrlBase($trackingCode, $postCode)
    {
        return self::BASE_URL."track?parcel=$trackingCode&postcode=$postCode";
    }

}
