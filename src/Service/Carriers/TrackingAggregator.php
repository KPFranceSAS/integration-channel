<?php

namespace App\Service\Carriers;

use App\Entity\WebOrder;
use App\Service\Carriers\CblLogisticTracking;
use App\Service\Carriers\CorreosExpTracking;
use App\Service\Carriers\DbSchenkerGetTracking;
use App\Service\Carriers\DhlGetTracking;
use App\Service\Carriers\SendingGetTracking;
use App\Service\Carriers\ShippyProTracking;
use DateTime;

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
                return $shippyProSteps ?: DhlGetTracking::checkIfDelivered($codeTracking);
            case WebOrder::CARRIER_ARISE:
                return AriseTracking::checkIfDelivered($codeTracking, $zipCode);
            case WebOrder::CARRIER_DPDUK:
                return DpdUkTracking::checkIfDelivered($codeTracking, $zipCode);
            case WebOrder::CARRIER_UPS:
            case WebOrder::CARRIER_SENDING:
            case WebOrder::CARRIER_CORREOSEXP:
            case WebOrder::CARRIER_TNT:
                return $this->shippyProTracking->checkIfDelivered($codeTracking);
        }
        return null;
    }


    public function findShippyProTracking($shipmentNumber): ?string
    {
        return $this->shippyProTracking->findTracking($shipmentNumber);
    }


    public function getFormattedSteps($carrier, $codeTracking, $zipCode=null): ?array
    {
        switch ($carrier) {
            case WebOrder::CARRIER_ARISE:
                return AriseTracking::getStepsTrackings($codeTracking, $zipCode);
            case WebOrder::CARRIER_DHL:
                $shippyProSteps = $this->shippyProTracking->getStepsTrackings($codeTracking);
                return $shippyProSteps ?: DhlGetTracking::getStepsTrackings($codeTracking);
            case WebOrder::CARRIER_DPDUK:
                return DpdUkTracking::getStepsTrackings($codeTracking, $zipCode);
            case WebOrder::CARRIER_UPS:
            case WebOrder::CARRIER_CORREOSEXP:
            case WebOrder::CARRIER_SENDING:
            case WebOrder::CARRIER_TNT:
                return $this->shippyProTracking->getStepsTrackings($codeTracking);
        }
        return null;
    }


    public function getTrackingUrlBase($carrier, $codeTracking, $zipCode=null)
    {
        return match ($carrier) {
            WebOrder::CARRIER_DHL => DhlGetTracking::getTrackingUrlBase($codeTracking),
            WebOrder::CARRIER_ARISE => AriseTracking::getTrackingUrlBase($codeTracking, $zipCode),
            WebOrder::CARRIER_DPDUK => DpdUkTracking::getTrackingUrlBase($codeTracking, $zipCode),
            WebOrder::CARRIER_DPDUK => DpdUkTracking::getTrackingUrlBase($codeTracking, $zipCode),
            WebOrder::CARRIER_UPS => UpsGetTracking::getTrackingUrlBase($codeTracking),
            WebOrder::CARRIER_DBSCHENKER => DbSchenkerGetTracking::getTrackingUrlBase($codeTracking),
            WebOrder::CARRIER_SENDING => SendingGetTracking::getTrackingUrlBase($codeTracking),
            WebOrder::CARRIER_TNT => TntGetTracking::getTrackingUrlBase($codeTracking),
            WebOrder::CARRIER_CORREOSEXP => CorreosExpTracking::getTrackingUrlBase($codeTracking),
            WebOrder::CARRIER_CBL => CblLogisticTracking::getTrackingUrlBase($codeTracking, $zipCode),
            default => null,
        };
    }
}
