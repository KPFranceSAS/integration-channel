<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Validator\Constraints as Assert;

class ProductCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }


    public function getName(): string
    {
        return 'Product';
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_AMAZON');
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
            TextField::new('asin')->setDisabled(),
            TextField::new('fnsku')->setDisabled(),
            IntegerField::new('minQtyFbaEu', 'Min FBA Eu'),
            IntegerField::new('minQtyFbaUk', 'Min FBA Uk'),
        ];
    }
}
