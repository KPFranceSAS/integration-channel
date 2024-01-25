<?php

namespace App\Helper\Utils;

use DateInterval;
use Symfony\Component\Validator\Constraints as Assert;


class InvoiceDownload
{


    #[Assert\NotBlank(message: 'Esta información es necesaria')]
    #[Assert\Length(minMessage: 'La longitud no es correcta', min: 10)]
    public $externalNumber;


    
    #[Assert\NotBlank(message: 'Esta información es necesaria')]
    public $dateInvoice;


    public function getDateStartString()
    {
        $dateStart = clone $this->dateInvoice;
        $dateStart->setTime(0, 0, 1);
        $dateStart->sub(new DateInterval('P1DT12H'));
        return $dateStart->format('Y-m-d H:i:s');
    }

    public function getDateEndString()
    {
        $dateEnd = clone $this->dateInvoice;
        $dateEnd->setTime(23, 59, 59);
        $dateEnd->add(new DateInterval('P1DT12H'));
        return $dateEnd->format('Y-m-d H:i:s');
    }
}
