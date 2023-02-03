<?php

namespace App\BusinessCentral\Model;

use App\BusinessCentral\Model\PostalAddress;

class SaleReturnOrder
{


    public $number;
    public $documentType;
    public $externalDocumentNo;
    public $postingDate;
    public $orderDate;
    public $shipmentDate;
    public $documentDate;
    public $sellToCustomerNo;
    public $billToCustomerNo;
    public $shipToCode;
    public $correctedInvoiceNo;
    public $packageTrackingNo;
    public $comentSAT;

    public function __construct()
    {
        $this->salesReturnOrderLines = [];
    }

    public $salesReturnOrderLines = [];


    public function transformToArray(): array
    {
        $transformArray = ['salesReturnOrderLines' => []];
        foreach ($this as $key => $value) {
            if ($key == 'salesReturnOrderLines') {
                foreach ($this->salesReturnOrderLines as $saleReturnOrderLine) {
                    $transformArray['salesReturnOrderLines'][] = $saleReturnOrderLine->transformToArray();
                }
            } elseif ($value !== null) {
                $transformArray[$key] = $value;
            }
        }
        return $transformArray;
    }
}
