<?php

namespace App\Channels\Mirakl;

use App\Channels\Mirakl\MiraklApiParent;
use App\Service\Aggregator\ProductSyncParent;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use stdClass;

abstract class MiraklSyncProductParent extends ProductSyncParent
{
    protected $productsApi;
    protected $categoriesApi;

    abstract protected function getLocale();

    protected function getMiraklApi(): MiraklApiParent
    {
        return $this->getApi();
    }


    protected function getNbLevels()
    {
        return 1;
    }



    public function syncProducts()
    {
    }
}
