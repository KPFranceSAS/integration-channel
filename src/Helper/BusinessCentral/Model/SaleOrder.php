<?php

namespace App\Helper\BusinessCentral\Model;

use App\Helper\BusinessCentral\Model\PostalAddress;

class SaleOrder
{
    const STATUS_OPEN = "Open";
    const STATUS_RELEASED = "Released";
    const STATUS_PENDING_APPROVAL = "Pending_Approval";
    const STATUS_PENDING_PREPAYMENT = "Pending_Prepayment";

    public $shippingPostalAddress;

    public $sellingPostalAddress;

    public $shipToName;

    public $billToName;

    public $customerNumber;

    public $customerId;

    public $externalDocumentNumber;

    public $number;

    public $currencyCode;

    public $pricesIncludeTax = true;

    public $paymentTermsId;

    public $shipmentMethodId;

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


    public function __construct()
    {
        $this->shippingPostalAddress = new PostalAddress();
        $this->sellingPostalAddress = new PostalAddress();
        $this->salesLines = [];
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
