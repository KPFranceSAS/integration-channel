<?php

namespace App\Tests\Service\Carriers;

use App\Service\Carriers\AriseTracking;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AriseServiceTest extends KernelTestCase
{
    public function testGetTracking(): void
    {
        $response = AriseTracking::getGlsResponse("314437110478070011",  "29140");
        dump($response);

        $response = AriseTracking::getGlsResponse("314437110449060010",  "47012");
        dump($response);

        $response = AriseTracking::getGlsResponse("314437110316450012",  "29140");
        dump($response);
    }
}
