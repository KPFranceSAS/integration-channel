<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\ProductSaleChannel;
use App\Entity\Promotion;
use App\Entity\SaleChannel;
use DateTime;
use PHPUnit\Framework\TestCase;

class PricingTest extends TestCase
{
    public function testPriceNormal(): void
    {
        $product =  $this->createProduct();
        $productAmz = $product->getProductSaleChannelByCode('amazon_test');
        $this->assertNotNull($productAmz, 'Product Sale channel Amz should not be null');
        $productAmz->setPrice(20);
        $price = $product->getRegularPriceOnMarketplace('amazon_test');
        $this->assertEquals(20, $price);
        $priceSale = $product->getSalePriceForNowOnMarketplace('amazon_test');
        $this->assertEquals(20, $price);
    }



    public function testPromotionFixedContinuous(): void
    {
        $product =  $this->createProduct();
        $productAmz = $product->getProductSaleChannelByCode('amazon_test');
        $productAmz->setPrice(20);
        $dateStart = DateTime::createFromFormat("Ymd Hi", '20220101 0100');
        $dateEnd = DateTime::createFromFormat("Ymd Hi", '20220130 2359');
        $promotion = $this->createPromotionFixed($dateStart, $dateEnd, 10.99);
        $productAmz->addPromotion($promotion);


        $dateJanuary = DateTime::createFromFormat("Ymd Hi", '20220115 2359');
        $priceSaleJanuary = $productAmz->getSalePrice($dateJanuary);
        $this->assertEquals(10.99, $priceSaleJanuary);

        $dateFebruary = DateTime::createFromFormat("Ymd Hi", '20220215 2359');
        $priceSaleFeb = $productAmz->getSalePrice($dateFebruary);
        $this->assertEquals(20, $priceSaleFeb);
    }



    public function testPromotionPercentContinuous(): void
    {
        $product =  $this->createProduct();
        $productAmz = $product->getProductSaleChannelByCode('amazon_test');
        $productAmz->setPrice(20);
        $dateStart = DateTime::createFromFormat("Ymd Hi", '20220101 0100');
        $dateEnd = DateTime::createFromFormat("Ymd Hi", '20220130 2359');
        $promotion = $this->createPromotionPercent($dateStart, $dateEnd, 10);
        $productAmz->addPromotion($promotion);


        $dateJanuary = DateTime::createFromFormat("Ymd Hi", '20220115 2359');
        $priceSaleJanuary = $productAmz->getSalePrice($dateJanuary);
        $this->assertEquals(18, $priceSaleJanuary);

        $dateFebruary = DateTime::createFromFormat("Ymd Hi", '20220215 2359');
        $priceSaleFeb = $productAmz->getSalePrice($dateFebruary);
        $this->assertEquals(20, $priceSaleFeb);
    }




    public function testPromotionFixedWeekend(): void
    {
        $product =  $this->createProduct();
        $productAmz = $product->getProductSaleChannelByCode('amazon_test');
        $productAmz->setPrice(20);
        $dateStart = DateTime::createFromFormat("Ymd Hi", '20220901 0100');
        $dateEnd = DateTime::createFromFormat("Ymd Hi", '20220930 2359');
        $promotion = $this->createPromotionFixed($dateStart, $dateEnd, 10.99);
        $promotion->setFrequency(Promotion::FREQUENCY_WEEKEND);
        $productAmz->addPromotion($promotion);


        $dateSept = DateTime::createFromFormat("Ymd Hi", '20220915 1200');
        $priceSaleSept = $productAmz->getSalePrice($dateSept);
        $this->assertEquals(20, $priceSaleSept);


        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220918 1200');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(10.99, $priceSaleSept);

        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 1800');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(10.99, $priceSaleSept);
     
        
        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220917 1759');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(10.99, $priceSaleSept);
    }




    public function testPromotionFixedTime(): void
    {
        $product =  $this->createProduct();
        $productAmz = $product->getProductSaleChannelByCode('amazon_test');
        $productAmz->setPrice(20);
        $dateStart = DateTime::createFromFormat("Ymd Hi", '20220901 0100');
        $dateEnd = DateTime::createFromFormat("Ymd Hi", '20220930 2359');
        $promotion = $this->createPromotionFixed($dateStart, $dateEnd, 10.99);
        $promotion->setFrequency(Promotion::FREQUENCY_TIMETOTIME);
        $promotion->setWeekDays([1,2,5]);
        $promotion->setBeginHour(DateTime::createFromFormat("Hi", '1800'));
        $promotion->setEndHour(DateTime::createFromFormat("Hi", '2200'));
        $productAmz->addPromotion($promotion);


        $dateSept = DateTime::createFromFormat("Ymd Hi", '20220914 2000');
        $priceSaleSept = $productAmz->getSalePrice($dateSept);
        $this->assertEquals(20, $priceSaleSept);

        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 1759');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(20, $priceSaleSept);

        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 2201');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(20, $priceSaleSept);

        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 1800');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(10.99, $priceSaleSept);
     
        
        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 2159');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(10.99, $priceSaleSept);


        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220912 1800');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(10.99, $priceSaleSept);
     
        
        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220912 2000');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(10.99, $priceSaleSept);
    }






    public function testPromotionConcurrentPriority(): void
    {
        $product =  $this->createProduct();
        $productAmz = $product->getProductSaleChannelByCode('amazon_test');
        $productAmz->setPrice(20);
        $dateStart = DateTime::createFromFormat("Ymd Hi", '20220901 0100');
        $dateEnd = DateTime::createFromFormat("Ymd Hi", '20220930 2359');

        $promotion = $this->createPromotionFixed($dateStart, $dateEnd, 15);
        $promotion->setFrequency(Promotion::FREQUENCY_TIMETOTIME);
        $promotion->setWeekDays([1,2,5]);
        $promotion->setBeginHour(DateTime::createFromFormat("Hi", '1800'));
        $promotion->setEndHour(DateTime::createFromFormat("Hi", '2200'));
        $promotion->setPriority(1);
        $productAmz->addPromotion($promotion);


        $promotion2 = $this->createPromotionFixed($dateStart, $dateEnd, 13);
        $promotion2->setPriority(2);
        $productAmz->addPromotion($promotion2);

        
        $promotion3 = $this->createPromotionFixed($dateStart, $dateEnd, 12);
        $promotion3->setFrequency(Promotion::FREQUENCY_TIMETOTIME);
        $promotion3->setWeekDays([1,2,5]);
        $promotion3->setBeginHour(DateTime::createFromFormat("Hi", '1800'));
        $promotion3->setEndHour(DateTime::createFromFormat("Hi", '2200'));
        $promotion3->setPriority(3);
        $productAmz->addPromotion($promotion3);


        $promotion4 = $this->createPromotionFixed($dateStart, $dateEnd, 11);
        $promotion4->setFrequency(Promotion::FREQUENCY_TIMETOTIME);
        $promotion4->setWeekDays([1,2,5]);
        $promotion4->setBeginHour(DateTime::createFromFormat("Hi", '1800'));
        $promotion4->setEndHour(DateTime::createFromFormat("Hi", '2200'));
        $promotion4->setPriority(10);
        $promotion4->setActive(false);
        $productAmz->addPromotion($promotion4);
        
        $promotion5 = $this->createPromotionFixed($dateStart, $dateEnd, 13.5);
        $promotion5->setPriority(2);
        $productAmz->addPromotion($promotion5);



        $dateSept = DateTime::createFromFormat("Ymd Hi", '20220914 2000');
        $priceSaleSept = $productAmz->getSalePrice($dateSept);
        $this->assertEquals(13, $priceSaleSept);

        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 1759');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(13, $priceSaleSept);

        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 2201');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(13, $priceSaleSept);

        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 1800');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(12, $priceSaleSept);
     
        
        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220916 2159');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(12, $priceSaleSept);


        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220912 1800');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(12, $priceSaleSept);
     
        
        $dateSeptWeekend = DateTime::createFromFormat("Ymd Hi", '20220912 2000');
        $priceSaleSept = $productAmz->getSalePrice($dateSeptWeekend);
        $this->assertEquals(12, $priceSaleSept);
    }











    public function createPromotion(DateTime $dateBegin, DateTime $dateEnd): Promotion
    {
        $promotion = new Promotion();
        $promotion->setEndDate($dateEnd);
        $promotion->setBeginDate($dateBegin);
        return $promotion;
    }



    public function createPromotionPercent(DateTime $dateBegin,DateTime $dateEnd, float $floatPercent ): Promotion
    {
        $promotion = $this->createPromotion($dateBegin, $dateEnd);
        $promotion->setPercentageAmount($floatPercent);
        $promotion->setDiscountType(Promotion::TYPE_PERCENT);
        return $promotion;
    }


    public function createPromotionFixed(DateTime $dateBegin,DateTime $dateEnd, float $floatPercent ): Promotion
    {
        $promotion = $this->createPromotion($dateBegin, $dateEnd);
        $promotion->setFixedAmount($floatPercent);
        $promotion->setDiscountType(Promotion::TYPE_FIXED);
        return $promotion;
    }




    public function createProduct(): Product
    {
        $product =  new Product();
        $product->setSku('SKU_TEST');
        $saleChannel = new SaleChannel();
        $saleChannel->setCode('amazon_test');
        $productSaleChannel = new ProductSaleChannel();
        $productSaleChannel->setEnabled(true);
        $saleChannel->addProductSaleChannel($productSaleChannel);
        $product->addProductSaleChannel($productSaleChannel);
        return $product;
    }
}
