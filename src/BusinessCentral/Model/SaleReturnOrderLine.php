<?php

namespace App\BusinessCentral\Model;

class SaleReturnOrderLine
{
    const TYPE_COMMENT = "Comment";
    const TYPE_GLACCOUNT = "Account";
    const TYPE_ITEM = "Item";

    public $documentNo;

    public $documentType;

    public $lineNo;

    public $type;

    public $number;

    public $quantity;

    public $quantityBase;

    public $returnQtyToReceive;

    public $returnQtyToReceiveBase;

    public $unitPrice;

    public $allowLineDisc = true;
    
    public $allowItemChargeAssignment = true;

    public $applFromItemEntry;

    public $locationCode;

    public $returnReasonCode;

    public function transformToArray()
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
