<?php

namespace App\Helper\BusinessCentral\Connector;

use App\Helper\BusinessCentral\Connector\Connector;

abstract class SaleDocumentConnector extends Connector
{

    abstract protected function getExtensionService();



    public function searchBySellToCustomerNo($numberCustomer, $limit = 0)
    {
        return  $this->searchBy("Sell_to_Customer_No", $numberCustomer, $limit);
    }

    public function searchByExternalDocumentNo($orderNumber)
    {
        return  $this->searchBy("External_Document_No", $orderNumber);
    }


    public function searchBy($field, $criteria, $limit = 0)

    {
        $filter = [
            [
                "Field" => $field,
                "Criteria" => $criteria
            ]
        ];
        return  $this->search($filter, $limit);
    }
}
