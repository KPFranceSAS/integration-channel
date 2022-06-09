<?php

namespace App\Service\Pim;

use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;

class AkeneoConnector
{
    private $client;

    public function __construct(
        string $akeneoUrl,
        string $akeneoClientId,
        string $akeneoClientSecret,
        string $akeneoUsername,
        string $akeneoPassword
    ) {
        $clientBuilder = new AkeneoPimClientBuilder($akeneoUrl);
        $this->client = $clientBuilder->buildAuthenticatedByPassword(
            $akeneoClientId,
            $akeneoClientSecret,
            $akeneoUsername,
            $akeneoPassword
        );
    }


    public function getAllProducts()
    {
        return $this->client->getProductApi()->all();
    }

    public function updateProduct($identifier, $values)
    {
        return $this->client->getProductApi()->upsert($identifier, $values);
    }


    public function getClient()
    {
        return $this->client;
    }
}
