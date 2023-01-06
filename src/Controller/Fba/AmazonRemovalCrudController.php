<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\AmazonRemoval;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AmazonRemovalCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return AmazonRemoval::class;
    }



    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_AMAZON');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
        $actions->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
            return $action->setIcon('fa fa-eye')->setLabel(false);
        });
        $actions->disable(Action::BATCH_DELETE, Action::NEW, Action::DELETE, Action::EDIT);
        return $actions;
    }

    public function getDefautOrder(): array
    {
        return ['requestDate' => "DESC"];
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('orderId'),
            TextField::new('amazonOrderId'),
            TextField::new('status')->setTemplatePath('admin/fields/amazon/status.html.twig'),
            TextField::new('orderType'),
            DateTimeField::new('requestDate'),
            DateTimeField::new('lastUpdateDate'),
            TextField::new('shipCity'),
            TextField::new('shipCountry'),
            CollectionField::new("amazonRemovalOrders", "Content")->onlyOnIndex(),
            CollectionField::new("amazonRemovalOrders")->setTemplatePath('admin/fields/amazon/amazonRemovals.html.twig')->onlyOnDetail()
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters;
    }
}
