<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Entity\ProductSaleChannel;

class ProductSaleChannelCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductSaleChannel::class;
    }


}
