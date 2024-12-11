<?php

namespace App\Channels\Amazon;

use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use App\Entity\IntegrationChannel;
use App\Entity\Product;
use App\Entity\ProductTypeCategorizacion;
use App\Entity\SaleChannel;
use App\Service\Aggregator\ProductSyncParent;
use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;

abstract class AmazonSyncProductParent extends ProductSyncParent
{
    abstract public function getChannel(): string;

    abstract public function getChannelPim(): string;

    abstract protected function getLocale(): string;

    protected $projectDir;


   
    protected function getLowerChannel()
    {
        return strtolower($this->getChannel());
    }


    public function syncProducts()
    {
       
    }






    protected function getProductsEnabledOnChannel()
    {
        $searchBuilder = new SearchBuilder();
        $searchBuilder
            ->addFilter('brand', 'NOT EMPTY')
            ->addFilter('ean', 'NOT EMPTY')
            ->addFilter('enabled_channel', '=', true, ['scope' => 'Marketplace'])
            ->addFilter('marketplaces_assignement', 'IN', [$this->getChannelPim()])
            ->addFilter('enabled', '=', true);

        return $this->akeneoConnector->searchProducts($searchBuilder, 'Marketplace');
    }


   
   

   
}
