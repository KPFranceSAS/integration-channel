<?php

namespace App\Controller\Admin;

use App\Entity\ProductCorrelation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCorrelationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductCorrelation::class;
    }



    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Product Correlation')
            ->setEntityLabelInPlural('Product Correlations');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('skuUsed'),
            TextField::new('skuErp'),
        ];
    }
}
