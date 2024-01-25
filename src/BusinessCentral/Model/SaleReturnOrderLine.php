<?php

namespace App\BusinessCentral\Model;

class SaleReturnOrderLine
{
    final public const TYPE_COMMENT = "Comment";
    final public const TYPE_GLACCOUNT = "Account";
    final public const TYPE_ITEM = "Item";

    public $documentNo;

    public $documentType;

    public $lineNo;

    public $type;

    public $ItemNo;

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
