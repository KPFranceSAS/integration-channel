<?php

namespace App\BusinessCentral\Model;

use App\BusinessCentral\Model\PostalAddress;
use App\Entity\WebOrder;
use App\Service\Carriers\UpsGetTracking;

class SaleOrder
{
    final public const STATUS_OPEN = "Open";
    final public const STATUS_RELEASED = "Released";
    final public const STATUS_PENDING_APPROVAL = "Pending_Approval";
    final public const STATUS_PENDING_PREPAYMENT = "Pending_Prepayment";

    public $shippingPostalAddress;

    public $sellingPostalAddress;

    public $shipToName;

    public $billToName;

    public $orderOrigin = 'MARKETPLACE';

    public $customerNumber;

    public $customerId;

    public $externalDocumentNumber;

    public $number;

    public $locationCode = WebOrder::DEPOT_LAROCA;

    public $currencyCode;

    public $pricesIncludeTax = true;

    public $paymentTermsId;

    public $paymentMethodCode;

    public $shipmentMethodId;

    public $shippingAgent = "DHLB2C";

    public $shippingAgentService = "DHLB2C";

    public $partialShipping;

    public $requestedDeliveryDate;

    public $discountAmount;

    public $discountAppliedBeforeTax;

    public $totalAmountExcludingTax;

    public $totalTaxAmount;

    public $totalAmountIncludingTax;

    public $status;

    public $phoneNumber;

    public $email;

    public $URLEtiqueta;


    public function __construct()
    {
        $this->shippingPostalAddress = new PostalAddress();
        $this->sellingPostalAddress = new PostalAddress();
    }

    public $salesLines = [];



    public function transformToArray(): array
    {
        $transformArray = ['salesOrderLines' => []];
        foreach ($this as $key => $value) {
            if ($key == 'salesLines') {
                foreach ($this->salesLines as $saleLine) {
                    $transformArray['salesOrderLines'][] = $saleLine->transformToArray();
                }
            } elseif (in_array($key, ['shippingPostalAddress', 'sellingPostalAddress'])) {
                $transformArray[$key] = $value->transformToArray();
            } elseif ($value !== null) {
                $transformArray[$key] = $value;
            }
        }
        return $transformArray;
    }
}
