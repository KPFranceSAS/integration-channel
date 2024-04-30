<?php

namespace App\Service\Carriers;

use DateInterval;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

class ShippyProTracking
{
    private $clientUrl='https://www.shippypro.com/api/v1';


    public function __construct(private readonly LoggerInterface $logger, private $shippyProKey)
    {
    }



    public function findTracking($shipmentNumber)
    {

        $dateTime = new DateTime();
        $dateEnd = $dateTime->format('U');
        $dateTime->sub(new DateInterval('P6M'));
        $dateStart = $dateTime->format('U');

        $result = $this->sendRequest(
            "GetShippedOrders",
            [
                'TransactionID'=>$shipmentNumber,
                'DateMin' => $dateStart,
                'DateMax' => $dateEnd,
            ]
        );

        if($result['Result']=='OK' && $result['Total']>0) {
            $firstTransaction = reset($result['Orders']);
            return $firstTransaction['tracking_code'];
        }
        return null;
    }


    public function getTracking($trackingNumber)
    {
        return $this->sendRequest("GetTracking", ['Code'=>$trackingNumber]);
    }



    public function checkIfDelivered($trackingNumber)
    {
        $tracking = $this->getTracking($trackingNumber);
        if(array_key_exists('StatusCode', $tracking) && $tracking['StatusCode']==6) {
            foreach($tracking['Details'] as $detail) {
                if($detail['StatusCode']==6) {
                    return DateTime::createFromFormat("U", $detail['date']);
                }
            }
        }
        return null;
    }



    public function getStepsTrackings($trackingNumber): ?array
    {
        $tracking = $this->getTracking($trackingNumber);
        if (array_key_exists('StatusCode', $tracking)) {
            $steps = [];
            foreach($tracking['Details'] as $step) {
                $dateEvent = DateTime::createFromFormat('U', $step['date']);
                $description = $step['message']. ' - '.$step['city'];
                $steps[ $dateEvent->format('YmdHis')]=[
                        'date'=>$dateEvent,
                        'description'=>$description
                ];
            }
            return $steps;
           
        }
        return null;
    }





    public function sendRequest($endPoint, $params = [])
    {
        $client = new Client();
        $headers = [
            'Authorization' => "Basic " . $this->shippyProKey,
            'Content-Type' => 'application/json'
        ];
        $body = [
                "Method"=> $endPoint,
                "Params"=> $params
        ];

        $request = new Request("GET", $this->clientUrl, $headers, json_encode($body));
        
        $response = $client->sendRequest($request);
        return json_decode($response->getBody(), true);
    }
}
