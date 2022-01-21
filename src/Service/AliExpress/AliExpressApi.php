<?php

namespace App\Service\AliExpress;

use AliexpressSolutionOrderGetRequest;
use AliexpressSolutionOrderInfoGetRequest;
use AmazonPHP\SellingPartner\Model\Reports\CreateReportSpecification;
use AmazonPHP\SellingPartner\STSClient;
use Buzz\Client\Curl;
use DateInterval;
use DateTime;
use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use OrderDetailQuery;
use OrderQuery;
use Psr\Log\LoggerInterface;
use TopClient;




class AliExpressApi
{


    private $aliExpressClientId;

    private $aliExpressClientSecret;

    private $aliExpressClientAccessToken;

    private $client;

    private $logger;


    public function __construct(LoggerInterface $logger, $aliExpressClientId, $aliExpressClientSecret, $aliExpressClientAccessToken)
    {
        $this->aliExpressClientAccessToken = $aliExpressClientAccessToken;
        $this->aliExpressClientId = $aliExpressClientId;
        $this->aliExpressClientSecret = $aliExpressClientSecret;
        $this->client = new TopClient($this->aliExpressClientId, $this->aliExpressClientSecret);
        $this->client->format = 'json';
        $this->logger = $logger;
    }


    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42270&docType=2
     *
     */
    public function getOrdersToSend()
    {
        $param0 = new OrderQuery();
        $param0->modified_date_start = "2021-12-08 00:00:00";
        $param0->order_status = "WAIT_SELLER_SEND_GOODS";
        return $this->getAllOrders($param0);
    }

    const PAGINATION = 50;

    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42270&docType=2
     *
     */
    public function getAllOrders(OrderQuery $param)
    {
        $current_page = 1;
        $max_page = 1;
        $orders = [];
        while ($current_page  <= $max_page) {
            $req = new AliexpressSolutionOrderGetRequest();
            $param->page_size = self::PAGINATION;
            $param->current_page = $current_page;
            $req->setParam0(json_encode($param));
            $this->logger->info('Get batch n°' . $current_page . ' >>' . json_encode($param));
            $reponse = $this->client->execute($req, $this->aliExpressClientAccessToken);
            if ($reponse->result->total_count > 0) {
                $orders = array_merge($orders, $reponse->result->target_list->order_dto);
            }

            $current_page++;
            $max_page  = $reponse->result->total_page;
        }

        return $orders;
    }




    /**
     * https://developers.aliexpress.com/en/doc.htm?docId=42707&docType=2
     */
    public function getOrder(string $orderNumber)
    {
        $req = new AliexpressSolutionOrderInfoGetRequest();
        $param1 = new OrderDetailQuery();
        $param1->order_id = $orderNumber;
        $req->setParam1(json_encode($param1));
        $resp = $this->client->execute($req, $this->aliExpressClientAccessToken);

        return $resp->result->data;
    }







    /**
     * go on your brower with the master account of aliexpress
     * And replace client_id value by $this->aliExpressClientId
     *  https://oauth.aliexpress.com/authorize?response_type=code&client_id=XXXXXXX&redirect_uri=https://aliexpress.gadgetiberia.es/es/module/aliexpress_official/auth?token=a1d930a3e4332d2c083978e8b5293b78&state=1212&view=web&sp=ae
     *  in the html request get the code of auth and use it in the command to regenerate the token. Token is valid for one year.
     */
    public function  getNewAccessToken($code)
    {
        $url = 'https://oauth.aliexpress.com/token';
        $postfields = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->aliExpressClientId,
            'client_secret' => $this->aliExpressClientSecret,
            'code' => $code,
            'sp' => 'ae',
            'redirect_uri' => 'http://www.oauth.net/2/'
        );
        $post_data = '';


        foreach ($postfields as $key => $value) {
            $post_data .= "$key=" . urlencode($value) . "&";
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_POST, true);


        curl_setopt($ch, CURLOPT_POSTFIELDS, substr($post_data, 0, -1));
        $output = curl_exec($ch);
        curl_close($ch);
        $reponse = json_decode($output, true);
        return $reponse;
    }
}
