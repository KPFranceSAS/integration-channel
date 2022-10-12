<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use Exception;

class BusinessCentralAggregator
{
    protected $services;


    public function __construct(
        iterable $services
    ) {
        foreach ($services as $service) {
            $this->services[$service->getCompanyIntegration()] = $service;
        }
    }




    public function getBusinessCentralConnector(string $companyName): BusinessCentralConnector
    {
        if (array_key_exists($companyName, $this->services)) {
            return $this->services[$companyName];
        }
        throw new Exception("Company $companyName is not related to any connector");
    }
}
