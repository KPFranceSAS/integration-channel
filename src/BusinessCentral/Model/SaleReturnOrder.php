<?php

namespace App\BusinessCentral\Model;

class SaleReturnOrder
{
    public $locationCode;
    public $no;
    public $documentType;
    public $externalDocumentNo;
    public $postingDate;
    public $orderDate;
    public $currencyCode;
    public $shipmentDate;
    public $documentDate;
    public $sellToCustomerNo;
    public $billToCustomerNo;
    public $shipToCode;
    public $correctInvoiceNo;
    public $packageTrackingNo;

    public $comentSat;

    public function __construct()
    {

    }

    


    public function transformToArray(): array
    {
        $transformArray = [];
        foreach ($this as $key => $value) {
            if ($value !== null) {
                $transformArray[$key] = $value;
            }
        }
        return $transformArray;
    }
}
