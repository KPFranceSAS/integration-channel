<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\ProductCorrelation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class ProductCorrelationCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductCorrelation::class;
    }


    public function getDefautOrder(): array
    {
        return ['skuUsed' => "ASC"];
    }

    public function getName(): string
    {
        return 'Sku Mapping';
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('product'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('skuUsed', 'SKU Marketplace'),
            AssociationField::new('product', 'SKU Business Central'),
        ];
    }
}
