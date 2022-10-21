<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\IntegrationChannel;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class IntegrationChannelCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return IntegrationChannel::class;
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setEntityPermission('ROLE_ADMIN');
        return $crud;
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->disable(Action::DELETE, Action::BATCH_DELETE, Action::NEW);
        return $actions;
    }




    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('code')->setDisabled(),
            TextField::new('name')->setDisabled(),
            BooleanField::new('active'),
            BooleanField::new('orderSync')->renderAsSwitch(false),
            BooleanField::new('stockSync')->renderAsSwitch(false),
            BooleanField::new('priceSync')->renderAsSwitch(false),
            BooleanField::new('productSync')->renderAsSwitch(false),
        ];
    }
}
