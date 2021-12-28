<?php

namespace App\Helper\BusinessCentral\Model;



class SaleOrderLine
{

    const TYPE_COMMENT = "Comment";
    const TYPE_GLACCOUNT = "Account";
    const TYPE_ITEM = "Item";
    const TYPE_RESOURCE = "Resource";
    const TYPE_FIXED_ASSET = "Fixed Asset";
    const TYPE_CHARGE_ITEM = "Charge";

    public $documentId;

    public $sequence;

    public $itemId;

    public $accountId;

    public $lineType;

    public $lineDetails;

    public $description;

    public $unitOfMeasure;

    public $quantity;

    public $unitPrice;

    public $discountAmount;

    public $discountPercent;

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
