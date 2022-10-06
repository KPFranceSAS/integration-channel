<?php

namespace App\BusinessCentral\Model;

class CustomerPayment
{
    public $customerNumber;

    public $customerId;

    public $externalDocumentNumber;

    public $documentNumber;

    public $postingDate;

    public $description;
    
    public $amount;

    public $comment;



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
