<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Brand;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BrandCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }



    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->update(Crud::PAGE_INDEX, Action::DELETE,  function (Action $action) {
            return $action->displayIf(static function ($entity) {
                return count($entity->getProducts()) == 0;
            });
        })->disable(Action::BATCH_DELETE);
        return $actions;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
        ];
    }
}
