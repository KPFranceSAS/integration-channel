<?php

namespace App\BusinessCentral;

use App\BusinessCentral\Connector\KitPersonalizacionSportConnector;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class MsrpPriceUpdate
{


    public function __construct(
        private LoggerInterface $logger,
        private KitPersonalizacionSportConnector $connector,
        private ManagerRegistry $managerRegistry)
    {
    }



    public function updateAllPrices(){
        
            $this->prices['EUR'] =  $this->createItemPrices($this->connector->getPricesPerGroup("PVP-ES"), 'EUR');
            $this->prices['GBP'] =  $this->createItemPrices($this->connector->getPricesPerGroup("PVP-UK"), 'GBP');
            $manager = $this->managerRegistry->getManager();
            $products = $manager->getRepository(Product::class)->findAll();


            foreach($products as $product){
                $product->setMsrpEur($this->getPrice($product->getSku(), 'EUR'));
                $product->setMsrpGbp($this->getPrice($product->getSku(), 'GBP'));
            }

            $manager->flush();



    }


    private $prices = [];
   

    private function getPrice($sku, $currency){
        return (array_key_exists($sku, $this->prices[$currency])) ? $this->prices[$currency][$sku] : null;

    }





    private function createItemPrices($itemPrices, $currencyCode)
    {
        $prices=[];
        foreach($itemPrices as $itemPrice) {
            if($this->checkValidityPrice($itemPrice)) {
               
                if ($itemPrice['PriceIncludesVAT']!=true) {
                    $vat = $currencyCode == 'EUR'  ? 1.21 : 1.2 ;
                    $finalPrice =  round($itemPrice['UnitPrice']* $vat, 2);
                } else {
                    $finalPrice =  round($itemPrice['UnitPrice'], 2);
                }            

                if(array_key_exists($itemPrice['ItemNo'], $prices)){
                    if($prices[$itemPrice['ItemNo']]>$finalPrice){
                        $prices[$itemPrice['ItemNo']]=$finalPrice;
                    }
                } else {
                    $prices[$itemPrice['ItemNo']]=$finalPrice;
                }              
                
            }
        }

        return $prices;
    }



    private function checkValidityPrice($itemPrice):bool
    {
        $now = date('Y-m-d');

        if ($itemPrice['EndingDate']=="0001-01-01") {
            $itemPrice['EndingDate'] = null;
        }


        if (strlen($itemPrice['MinimumQuantity'])==0) {
            $itemPrice['MinimumQuantity']=0;
        }

        if ($itemPrice['StartingDate']=="0001-01-01") {
            $itemPrice['StartingDate'] = null;
        }


        if ($itemPrice['EndingDate'] && $itemPrice['EndingDate'] < $now) {
            return false;
        }

        if ($itemPrice['StartingDate'] && $itemPrice['StartingDate'] > $now) {
            return false;
        }


        if ($itemPrice['UnitPrice']==0) {
            return false;
        }
        return true;
    }


}
