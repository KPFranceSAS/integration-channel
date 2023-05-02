<?php

namespace App\BusinessCentral\Model;

class SaleReturnOrder
{

    public $no;
    public $documentType;
    public $externalDocumentNo;
    public $postingDate;
    public $orderDate;
    public $shipmentDate;
    public $documentDate;
    public $sellToCustomerNo;
    public $billToCustomerNo;
    public $shipToCode;
    public $correctInvoiceNo;
    public $packageTrackingNo;
    public $comentSAT;

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
