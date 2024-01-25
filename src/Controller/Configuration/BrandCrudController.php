<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Brand;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BrandCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }

    public function getDefautOrder(): array
    {
        return ['name' => "ASC"];
    }



    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_ADMIN');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action->displayIf(static fn($entity) => $entity && count($entity->getProducts()) == 0))->disable(Action::BATCH_DELETE);
        return $actions;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setDisabled(true),
            NumberField::new('stockBuffer')->setRequired(true),
        ];
    }
}
