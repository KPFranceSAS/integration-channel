<?php

namespace App\BusinessCentral\Connector;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Connector\KpFranceConnector;
use Exception;

class BusinessCentralAggregator
{
    private $kpFranceConnector;

    private $gadgetIberiaConnector;

    private $kitPersonalizacionSportConnector;

    public function __construct(
        KpFranceConnector $kpFranceConnector,
        GadgetIberiaConnector $gadgetIberiaConnector,
        KitPersonalizacionSportConnector $kitPersonalizacionSportConnector
    ) {
        $this->kpFranceConnector = $kpFranceConnector;
        $this->gadgetIberiaConnector = $gadgetIberiaConnector;
        $this->kitPersonalizacionSportConnector = $kitPersonalizacionSportConnector;
    }


    public function getBusinessCentralConnector(string $companyName): BusinessCentralConnector
    {
        if ($companyName == BusinessCentralConnector::KP_FRANCE) {
            return $this->kpFranceConnector;
        } elseif ($companyName == BusinessCentralConnector::GADGET_IBERIA) {
            return $this->gadgetIberiaConnector;
        } elseif ($companyName == BusinessCentralConnector::KIT_PERSONALIZACION_SPORT) {
            return $this->kitPersonalizacionSportConnector;
        }


        throw new Exception("Company $companyName is not related to any connector");
    }
}
