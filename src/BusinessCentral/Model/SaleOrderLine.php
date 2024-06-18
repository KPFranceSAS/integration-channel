<?php

namespace App\BusinessCentral\Model;

class SaleOrderLine
{
    final public const TYPE_COMMENT = "Comment";
    final public const TYPE_GLACCOUNT = "Account";
    final public const TYPE_ITEM = "Item";
    final public const TYPE_RESOURCE = "Resource";
    final public const TYPE_FIXED_ASSET = "Fixed Asset";
    final public const TYPE_CHARGE_ITEM = "Charge";

    public $documentId;

    public $sequence;

    public $itemId;

    public $accountId;

    public $lineType;

    public $lineDetails;

    public $shipmentDate;

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
