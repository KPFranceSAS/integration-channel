<?php

namespace App\Service\Carriers;

use App\Entity\WebOrder;
use App\Service\Carriers\DhlGetTracking;
use App\Service\Carriers\ShippyProTracking;
use DateTime;
use Exception;

class TrackingAggregator
{
    protected $shippyProTracking;

    public function __construct(
        ShippyProTracking $shippyProTracking
    ) {
        $this->shippyProTracking = $shippyProTracking;
    }

    public function checkIfDelivered($carrier, $codeTracking, $zipCode=null): ?DateTime
    {
        switch ($carrier) {
            case WebOrder::CARRIER_DHL:
                $shippyProSteps = $this->shippyProTracking->checkIfDelivered($codeTracking);
                return $shippyProSteps ? $shippyProSteps :  DhlGetTracking::checkIfDelivered($codeTracking);
            case WebOrder::CARRIER_ARISE:
                return AriseTracking::checkIfDelivered($codeTracking, $zipCode);
            case WebOrder::CARRIER_UPS:
                return $this->shippyProTracking->checkIfDelivered($codeTracking);
        }
        return null;
    }





    public function getFormattedSteps($carrier, $codeTracking, $zipCode=null): ?array
    {
        switch ($carrier) {
            case WebOrder::CARRIER_ARISE:
                return AriseTracking::getStepsTrackings($codeTracking, $zipCode);
            case WebOrder::CARRIER_DHL:
                $shippyProSteps = $this->shippyProTracking->getStepsTrackings($codeTracking);
                return $shippyProSteps ? $shippyProSteps : DhlGetTracking::getStepsTrackings($codeTracking);
            case WebOrder::CARRIER_UPS:
                return $this->shippyProTracking->getStepsTrackings($codeTracking);
        }
        return null;
    }


    public function getTrackingUrlBase($carrier, $codeTracking, $zipCode=null)
    {
        switch ($carrier) {
            case WebOrder::CARRIER_DHL:
                return DhlGetTracking::getTrackingUrlBase($codeTracking);
            case WebOrder::CARRIER_ARISE:
                return AriseTracking::getTrackingUrlBase($codeTracking, $zipCode);
            case WebOrder::CARRIER_UPS:
                return UpsGetTracking::getTrackingUrlBase($codeTracking);
        }
        return null;
    }
}
