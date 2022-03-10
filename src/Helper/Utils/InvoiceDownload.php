<?php

namespace App\Helper\Utils;

use Symfony\Component\Validator\Constraints as Assert;


class InvoiceDownload
{


    /**
     * @Assert\NotBlank(message="Esta información es necesaria")
     * @Assert\Length(minMessage="La longitud no es correcta", min=10)
     */
    public $externalNumber;


    /**
     * 
     * @Assert\NotBlank(message="Esta información es necesaria")
     * 
     */
    public $dateInvoice;
}
