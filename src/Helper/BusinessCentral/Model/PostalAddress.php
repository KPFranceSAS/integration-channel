<?php

namespace App\Helper\BusinessCentral\Model;


class PostalAddress
{

    public $street;

    public $city;

    public $postalCode;

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
