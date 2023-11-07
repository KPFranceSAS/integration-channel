<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\LogisticClass;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class LogisticClassCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return LogisticClass::class;
    }

    public function getDefautOrder(): array
    {
        return ['code' => "ASC"];
    }



    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_ADMIN');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
            return $action->displayIf(static function ($entity) {
                return $entity && count($entity->getProducts()) == 0;
            });
        })->disable(Action::BATCH_DELETE);
        return $actions;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('code')->setRequired(true),
            TextField::new('label')->setRequired(true),
        ];
    }
}
