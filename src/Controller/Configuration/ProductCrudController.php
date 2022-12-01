<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
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




    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku')->setDisabled(),
            AssociationField::new('brand'),
            AssociationField::new('category'),
            TextField::new('description', 'Product name'),
            DateTimeField::new('createdAt', "Created at")
        ];
    }
    

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('brand'))
            ->add(EntityFilter::new('category'))
            ->add(TextFilter::new('sku'));
    }
}
