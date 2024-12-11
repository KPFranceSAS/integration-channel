<?php

namespace App\Channels\Amazon;

use AmazonPHP\SellingPartner\AccessToken;
use AmazonPHP\SellingPartner\Configuration;
use AmazonPHP\SellingPartner\Model\Tokens\CreateRestrictedDataTokenRequest;
use AmazonPHP\SellingPartner\Regions;
use AmazonPHP\SellingPartner\SellingPartnerSDK;
use AmazonPHP\SellingPartner\STSClient;
use Buzz\Client\Curl;
use DateTime;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;

class AmazonApiClient
{
    protected $client;

    protected $clientUrl;


    protected $clientKey;

    protected $contractId;

    protected $accessRestrictedToken;


    private $dateInitialisationToken;

    private $sdk;

    private $accessToken;

    public function __construct(private readonly LoggerInterface $logger, private readonly string $amzLwaId, private readonly string $amzLwaSecret, private readonly string $amzAwsId, private readonly string $amzAwsSecret, private readonly string $amzArn, private readonly string $amzRefreshToken, private readonly string $amzSellerId)
    {
        $factory = new Psr17Factory();
        $client = new Curl($factory);

        $sts = new STSClient(
            $client,
            $factory,
            $factory
        );

        $configuration =  Configuration::forIAMRole(
            $this->amzLwaId,
            $this->amzLwaSecret,
            $sts->assumeRole(
                $this->amzAwsId,
                $this->amzAwsSecret,
                $this->amzArn
            )
        );
        $this->sdk = SellingPartnerSDK::create($client, $factory, $factory, $configuration, $this->logger);
    }


    public function getSdk()
    {
        return $this->sdk;
    }
    


    public function getAccessToken()
    {
        if ($this->checkIfWeNeedNewToken()) {
            $this->accessToken = $this->sdk->oAuth()->exchangeRefreshToken($this->amzRefreshToken);
            $this->dateInitialisationToken = new DateTime();
        }
        return $this->accessToken;
    }




    public function getRestrictedToken()
    {
        if (!$this->accessRestrictedToken) {
            $createRestrictedDataTokenRequest = new CreateRestrictedDataTokenRequest();
            $createRestrictedDataTokenRequest->setRestrictedResources([
                [
                    "method"=> "GET",
                    "path" => "/orders/v0/orders",
                    "dataElements" => ["buyerInfo", "shippingAddress"]
                ]
            ]);
            //$createRestrictedDataTokenRequest->setTargetApplication('amzn1.sp.solution.a9b707b1-3382-4b95-8e70-d4dc3f3d10a1');

            
            $reponse =$this->sdk->tokens()->createRestrictedDataToken($this->getAccessToken(), Regions::EUROPE, $createRestrictedDataTokenRequest);
            $this->accessRestrictedToken = new AccessToken($reponse->getRestrictedDataToken(), $reponse->getRestrictedDataToken(), $reponse->getRestrictedDataToken(), $reponse->getExpiresIn(), 'security');
        }
        return $this->accessRestrictedToken;
    }


    private function checkIfWeNeedNewToken()
    {
        if (!$this->accessToken || !$this->dateInitialisationToken) {
            return true;
        }
        $dateNow = new DateTime();
        $diffMin = abs($dateNow->getTimestamp() - $this->dateInitialisationToken->getTimestamp());
        return $this->accessToken->expiresIn() < $diffMin;
    }


   



}
