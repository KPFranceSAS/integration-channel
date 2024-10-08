<?php

namespace App\BusinessCentral\Model;

class JournalLine
{
    public $accountNumber;

    public $postingDate;

    public $documentNumber;

    public $externalDocumentNumber;

    public $amount;
    
    public $description;

    public $comment;

    public $balAccountType;

    public $BalAccountNo;


    public $documentType;

    public $accountType;

    public $appliesToDocNo;

    public $appliesToDocType;


    
    public function transformToCreate(): array
    {
        return [
            'externalDocumentNumber' => $this->externalDocumentNumber
        ];
    }

   

    public function transformTo1stPatch(): array
    {
        return [
            'accountType' => $this->accountType
        ];
    }


    public function transformTo2ndPatch(): array
    {
        $transformArray = $this->transformToArray();
        unset($transformArray['externalDocumentNumber']);
        unset($transformArray['accountType']);
        return $transformArray;
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
