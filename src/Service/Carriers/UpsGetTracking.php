<?php

namespace App\Service\Carriers;

use function Symfony\Component\String\u;

class UpsGetTracking
{
    public static function getTrackingUrlBase($codeTracking)
    {
        return "https://wwwapps.ups.com/WebTracking/track?loc=en_IT&trackNums=" . $codeTracking;
    }



    public static function shouldBeSentWith(array $skus):bool
    {
        foreach ($skus as $sku) {
            if (self::isThisSkuShouldBeSendWithUps($sku)) {
                return true;
            }
        }
        return false;
    }


    public static function isThisSkuShouldBeSendWithUps($sku):bool
    {
        if (u($sku)->startsWith('ANK-')) {
            return true;
        }
        return false;
    }
}
