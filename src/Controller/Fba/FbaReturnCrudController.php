<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\FbaReturn;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FbaReturnCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return FbaReturn::class;
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->disable(Action::BATCH_DELETE, Action::NEW, Action::DELETE, Action::EDIT);
        return $actions;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku'),

        ];
    }
}
