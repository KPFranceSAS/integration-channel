<?php

namespace App\Helper\BusinessCentral\Model;


class PostalAddress
{

    public $street;

    public $postalCode;

    public $city;

    public $countryLetterCode;

    public $state;



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
