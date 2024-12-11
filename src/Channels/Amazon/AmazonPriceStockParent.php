<?php

namespace App\Channels\Amazon;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\BusinessCentral\ProductStockFinder;
use App\BusinessCentral\ProductTaxFinder;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Helper\MailService;
use App\Service\Aggregator\ApiAggregator;
use App\Service\Aggregator\PriceStockParent;
use App\Service\Carriers\UpsGetTracking;
use Doctrine\Persistence\ManagerRegistry;
use League\Csv\Writer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AmazonPriceStockParent extends PriceStockParent
{
    
   abstract protected function getCountryCode();
 

    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }


    protected function getAmazonApi(): AmazonApiParent
    {
        return $this->getApi();
    }


    public function sendStocksPrices(array $products, array $saleChannels)
    {
        
    }

   


   
}
