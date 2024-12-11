<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;
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


    public function getAllCompanies(): array
    {
        return  [
            BusinessCentralConnector::KP_FRANCE,
            BusinessCentralConnector::GADGET_IBERIA,
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
            BusinessCentralConnector::INIA,
            BusinessCentralConnector::KP_UK,
            BusinessCentralConnector::TURISPORT
        ];
    }



    public function getInitiales($companyName)
    {
        if ($companyName == BusinessCentralConnector::KP_FRANCE) {
            return 'kpf';
        } elseif ($companyName == BusinessCentralConnector::GADGET_IBERIA) {
            return 'gi';
        } elseif ($companyName == BusinessCentralConnector::KIT_PERSONALIZACION_SPORT) {
            return 'kps';
        } elseif ($companyName == BusinessCentralConnector::INIA) {
            return 'inia';
        } elseif ($companyName == BusinessCentralConnector::KP_UK) {
            return 'kpuk';
        } elseif ($companyName == BusinessCentralConnector::TURISPORT) {
            return 'turi';
        }
        throw new Exception("Company $companyName is not related to any connector");
    }
}
