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
            ->setEntityLabelInSingular('Sku Mapping')
            ->setEntityLabelInPlural('Sku Mappings')
            ->showEntityActionsInlined();
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('skuUsed', 'SKU Marketplace'),
            TextField::new('skuErp', 'SKU Business Central'),
        ];
    }
}
