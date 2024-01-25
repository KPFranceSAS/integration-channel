<?php

namespace App\Channels\Shopify\PaxUk;

class PaxHelper
{

    public static function getBusinessCentralSku($sku)
    {
        $skusWithPrefix = self::getSkuWithPxPrefix();
        return (in_array($sku, $skusWithPrefix)) ? 'PX-'.$sku : 'PAX-'.$sku;
    }



    public static function getShopifySku($sku)
    {
        return str_replace(['PX-', 'PAX-'], '', (string) $sku);
    }
    


    public static function getSkuWithPxPrefix()
    {
        return [
            "P3D2576",
            "P3D2541",
            "P3D2456",
            "P3D2455",
            "P3D2454",
            "P3D2453",
            "P3D2452",
            "P3D2451",
            "P3D2450",
            "P3D2449",
            "P3D2053",
            "P3D2052",
            "P3D2051",
            "P3D2050",
            "P3D2045",
            "P3D2044",
            "P3D2043",
            "P3D2042",
            "P2D2077",
            "P2D2076",
            "P2A1844",
            "P2A1823",
            "P2A1741",
            "P2A1740",
            "P2A1738",
            "P2A1737",
            "P2A1022",
            "P2A1020",
            "P2A1018",
            "P2A1017",
            "P2A1016",
        ];
    }

}
