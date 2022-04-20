<?php

namespace App\Controller\Admin;

use App\Controller\Admin\AdminCrudController;
use App\Entity\ProductCorrelation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCorrelationCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductCorrelation::class;
    }


    public function getName(): string
    {
        return 'Sku Mapping';
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('skuUsed', 'SKU Marketplace'),
            TextField::new('skuErp', 'SKU Business Central'),
        ];
    }
}
