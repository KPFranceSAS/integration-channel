<?php

namespace App\Tests\Service\BusinessCentral;

use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\GadgetIberiaConnector;
use App\Service\BusinessCentral\ProductTaxFinder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UpdateStatusDeliveryTest extends KernelTestCase
{
    public function getContentFromGadgetIberia(): void
    {
        $updateStatusDelivery = static::getContainer()->get(UpdateStatusDelivery::class);

        //$this->assertGreaterThan(0, $canonDigital, 'Canon digital defined');
    }
}
