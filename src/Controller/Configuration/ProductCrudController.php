<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class ProductCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function getDefautOrder(): array
    {
        return ['sku' => "ASC"];
    }

    public function getName(): string
    {
        return 'Product';
    }



    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_ADMIN');
    }




    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku')->setDisabled(),
            TextField::new('ean')->setDisabled(),
            AssociationField::new('brand'),
            AssociationField::new('category'),
            AssociationField::new('logisticClass'),
            TextField::new('description', 'Product name'),
            BooleanField::new('active')->setDisabled(),
            BooleanField::new('dangerousGood')->renderAsSwitch(true),
            BooleanField::new('freeShipping')->renderAsSwitch(false),
            DateTimeField::new('createdAt', "Created at")
        ];
    }
    

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('brand'))
            ->add(EntityFilter::new('category'))
            ->add(EntityFilter::new('logisticClass'))
            ->add(TextFilter::new('sku'))
            ->add(BooleanFilter::new('dangerousGood'))
            ->add(BooleanFilter::new('freeShipping'));
    }
}
