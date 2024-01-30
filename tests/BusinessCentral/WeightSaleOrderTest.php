<?php

namespace App\Tests\BusinessCentral;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\BusinessCentral\Model\SaleOrder;
use App\BusinessCentral\Model\SaleOrderLine;
use App\BusinessCentral\SaleOrderWeightCalculation;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WeightSaleOrderTest extends KernelTestCase
{
    public function testWeightSimple(): void
    {
        $saleCal= static::getContainer()->get(SaleOrderWeightCalculation::class);
        $bcConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
    
        $saleOrder = new SaleOrder();
        $saleOrderLine = new SaleOrderLine();
        $saleOrderLine->lineType = SaleOrderLine::TYPE_ITEM;
        $saleOrderLine->quantity = 1;
        $itemBc = $bcConn->getItemByNumber("X-MZB08KWEU");
        $saleOrderLine->itemId = $itemBc['id'];
        $saleOrder->salesLines[]=$saleOrderLine;
        dump($saleCal->calculateWeight($saleOrder));
    }


    public function testWeightQty(): void
    {
        $saleCal= static::getContainer()->get(SaleOrderWeightCalculation::class);
        $bcConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
    
        $saleOrder = new SaleOrder();
        $saleOrderLine = new SaleOrderLine();
        $saleOrderLine->lineType = SaleOrderLine::TYPE_ITEM;
        $saleOrderLine->quantity = 10;
        $itemBc = $bcConn->getItemByNumber("X-MZB08KWEU");
        $saleOrderLine->itemId = $itemBc['id'];
        $saleOrder->salesLines[]=$saleOrderLine;
        dump($saleCal->calculateWeight($saleOrder));
    }


    public function testMultiLines(): void
    {
        $saleCal= static::getContainer()->get(SaleOrderWeightCalculation::class);
        $bcConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
    
        $saleOrder = new SaleOrder();
        $saleOrderLine = new SaleOrderLine();
        $saleOrderLine->lineType = SaleOrderLine::TYPE_ITEM;
        $saleOrderLine->quantity = 10;
        $itemBc = $bcConn->getItemByNumber("X-MZB08KWEU");
        $saleOrderLine->itemId = $itemBc['id'];
        $saleOrder->salesLines[]=$saleOrderLine;

        $saleOrderLine2 = new SaleOrderLine();
        $saleOrderLine2->lineType = SaleOrderLine::TYPE_ITEM;
        $saleOrderLine2->quantity = 5;
        $itemBc = $bcConn->getItemByNumber("PAX-P3D2578");
        $saleOrderLine2->itemId = $itemBc['id'];
        $saleOrder->salesLines[]=$saleOrderLine2;

        dump($saleCal->calculateWeight($saleOrder));
    }


    public function testBundle(): void
    {
        $saleCal= static::getContainer()->get(SaleOrderWeightCalculation::class);
        $bcConn = static::getContainer()->get(KitPersonalizacionSportConnector::class);
    
        $saleOrder = new SaleOrder();
        $saleOrderLine = new SaleOrderLine();
        $saleOrderLine->lineType = SaleOrderLine::TYPE_ITEM;
        $saleOrderLine->quantity = 1;
        $itemBc = $bcConn->getItemByNumber("ANK-PCK-2");
        $saleOrderLine->itemId = $itemBc['id'];
        $saleOrder->salesLines[]=$saleOrderLine;

         dump($saleCal->calculateWeight($saleOrder));
    }


}
