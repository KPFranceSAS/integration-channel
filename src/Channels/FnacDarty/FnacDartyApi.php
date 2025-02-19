<?php

namespace App\Channels\FnacDarty;

use App\Channels\Mirakl\MiraklApiParent;
use Exception;
use Psr\Log\LoggerInterface;
use Twig\Environment;

abstract class FnacDartyApi extends MiraklApiParent
{
    abstract public function getChannel();
   

    
    public function __construct(
        LoggerInterface $logger,
        Environment $twig,
        protected $fnacDartyClientUrl,
        protected $fnacDartyClientPartnerId,
        protected $fnacDartyClientShopId,
        protected $fnacDartyClientKey,
        $projectDir,
        $fnacDartyMiraklClientUrl,
        $fnacDartyMiraklClientKey
    ) {
        $this->logger= $logger;
        $this->twig =  $twig;
        parent::__construct($logger, $projectDir, $fnacDartyMiraklClientUrl, $fnacDartyMiraklClientKey);
    }




    protected $logger;

    protected $twig;

    protected $fnacToken;
    


    public function getToken()
    {
        if(!$this->fnacToken) {
            $xmlGenerated = $this->twig->render('fnac/auth.xml.twig', [
                'fnacDartyClientKey'=> $this->fnacDartyClientKey,
                'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
                'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
            ]);
            $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
            $response    = $this->doPostRequest("auth", $xmlAuthentication->asXML());
            $xmlResponse = simplexml_load_string(trim((string) $response));
            $this->fnacToken = $xmlResponse->token;
        }
        return $this->fnacToken;
    }




    public function sendOffers($offers, $toDeletes = [])
    {
        $xmlGenerated = $this->twig->render('fnac/offers_update.xml.twig', [
            'fnacDartyClientToken'=> $this->getToken(),
            'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
            'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
            'offers' => $offers,
            'toDeletes' => $toDeletes
        ]);
        $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
        $response    = $this->doPostRequest("offers_update", $xmlAuthentication->asXML());
        $xmlResponse = simplexml_load_string(trim((string) $response));
        if((string)$xmlResponse->attributes()->status=='OK') {
            $this->logger->info('Created batch '.$xmlResponse->batch_id);
            return $xmlResponse->batch_id;
        } else {
            throw new Exception('Error in xml '.$response);
        }
        
    }



    public function updateOrder($orderId, $status): bool
    {
        $xmlGenerated = $this->twig->render('fnac/orders_status.xml.twig', [
            'fnacDartyClientToken'=> $this->getToken(),
            'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
            'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
            'orderId' => $orderId,
            'status' => $status
        ]);
        $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
        $response    = $this->doPostRequest("orders_update", $xmlAuthentication->asXML());
        $xmlResponse = simplexml_load_string(trim((string) $response));
        return (string)$xmlResponse->attributes()->status=='OK';
    }


    public function getBatches(): bool
    {
        $xmlGenerated = $this->twig->render('fnac/batch_query.xml.twig', [
            'fnacDartyClientToken'=> $this->getToken(),
            'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
            'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
        ]);
        $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
        $response    = $this->doPostRequest("batch_query", $xmlAuthentication->asXML());
        $xmlResponse = simplexml_load_string(trim((string) $response));
        return $xmlResponse;
    }

    public function getBatchStatusId($batchId): bool
    {
        $xmlGenerated = $this->twig->render('fnac/batch_status.xml.twig', [
            'fnacDartyClientToken'=> $this->getToken(),
            'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
            'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
            'batchId' => $batchId,
        ]);
        $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
        $response    = $this->doPostRequest("batch_status", $xmlAuthentication->asXML());
        $xmlResponse = simplexml_load_string(trim((string) $response));
        return $xmlResponse;
    }



    public function markOrderFulfilled($orderId, $carrierCode, $trackingNumber):bool
    {
        $xmlGenerated = $this->twig->render('fnac/orders_shipped.xml.twig', [
            'fnacDartyClientToken'=> $this->getToken(),
            'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
            'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
            'orderId' => $orderId,
            'carrierCode' => $carrierCode,
            'trackingNumber' => $trackingNumber
        ]);
        $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
        $response    = $this->doPostRequest("orders_update", $xmlAuthentication->asXML());
        $xmlResponse = simplexml_load_string(trim((string) $response));
        return (string)$xmlResponse->attributes()->status=='OK';
    }


    public function getAllCarriers()
    {
        $xmlGenerated = $this->twig->render('fnac/carriers_query.xml.twig', [
            'fnacDartyClientToken'=> $this->getToken(),
            'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
            'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
        ]);
        $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
        $response    = $this->doPostRequest("carriers_query", $xmlAuthentication->asXML());
        return $response;
    }


    

    public function markOrderAsAccepted($orderId): bool
    {
        return $this->updateOrder($orderId, 'Accepted');
    }
    

    public function markOrderAsRefused($orderId): bool
    {
        return $this->updateOrder($orderId, 'Refused');
    }


    public function getAllOrdersToAccept()
    {
        return $this->getAllOrders(['state'=>'Created']);
    }


    public function getAllOrdersToSend()
    {
        return $this->getAllOrders(['state'=>'ToShip']);
    }




    public function getAllOrders(array $params)
    {

        $offset = 0;
        $maxPage = 1;
        $orders = [];
        while ($offset < $maxPage) {
            $this->logger->info('Page '.$offset);
            $offset++;
            $xmlGenerated = $this->twig->render('fnac/orders_query.xml.twig', [
                'fnacDartyClientToken'=> $this->getToken(),
                'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
                'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
                'params' => $params,
                'pagination' => 50,
                'paging' => $offset
            ]);
            $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
            $response    = $this->doPostRequest("orders_query", $xmlAuthentication->asXML());
            $xmlResponse = simplexml_load_string(trim((string) $response), 'SimpleXMLElement', LIBXML_NOCDATA);
            $reponse = json_decode(json_encode((array)$xmlResponse), true);

            if(array_key_exists('order', $reponse)) {
                if(array_key_exists('order_id', $reponse['order'])) {
                    $orders = array_merge($orders, [$reponse['order']]);
                } else {
                    $orders = array_merge($orders, $reponse['order']);
                }
               
            }
            $maxPage = (int)$xmlResponse->total_paging;
        }

        foreach ($orders as $k=>$order) {
            if(array_key_exists('order_detail_id', $order['order_detail'])) {
                $orders[$k]['order_detail']=[$order['order_detail']];
            }
        }
        return $orders;
    }





    public function getOffers(array $params= []): array
    {

        $offset = 0;
        $maxPage = 1;
        $offers = [];
        while ($offset < $maxPage) {
            $this->logger->info('Page '.$offset);
            $offset++;
            $xmlGenerated = $this->twig->render('fnac/offers_query.xml.twig', [
                'fnacDartyClientToken'=> $this->getToken(),
                'fnacDartyClientPartnerId'=> $this->fnacDartyClientPartnerId,
                'fnacDartyClientShopId'=> $this->fnacDartyClientShopId,
                'params' => $params,
                'pagination' => 50,
                'paging' => $offset
            ]);
            $xmlAuthentication  = simplexml_load_string((string) $xmlGenerated);
            $response    = $this->doPostRequest("offers_query", $xmlAuthentication->asXML());
            $xmlResponse = simplexml_load_string(trim((string) $response), 'SimpleXMLElement', LIBXML_NOCDATA);
            $reponse = json_decode(json_encode((array)$xmlResponse), true);
            if(array_key_exists('offer', $reponse)) {
                if(array_key_exists('offer_fnac_id', $reponse['offer'])) {
                    $offers = array_merge($offers, [$reponse['offer']]);
                } else {
                    $offers = array_merge($offers, $reponse['offer']);
                }
               
            }
            $maxPage = (int)$xmlResponse->total_paging;
        }
        return $offers;
    }




    public function doPostRequest($url, $data)
    {
        $ch = curl_init();

        // Depending on your system, you may add other options or modify the following ones.
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->fnacDartyClientUrl.$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
