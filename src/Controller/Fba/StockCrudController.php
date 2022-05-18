<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class StockCrudController extends AdminCrudController
{
    public function getDefautOrder(): array
    {
        return ['ratioStock' => "DESC"];
    }


    public static function getEntityFqcn(): string
    {
        return Product::class;
    }


    public function getName(): string
    {
        return 'Stock';
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_AMAZON');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL, ACTION::EDIT);
    }




    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku')->setDisabled(),
            TextField::new('description', 'Product name'),
            IntegerField::new('fbaSellableStock'),
            IntegerField::new('fbaUnsellableStock'),
            IntegerField::new('soldStockNotIntegrated'),
            IntegerField::new('returnStockNotIntegrated'),
            IntegerField::new('businessCentralStock'),
            PercentField::new('ratioStock')
        ];
    }
}
