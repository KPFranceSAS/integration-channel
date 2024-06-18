<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\AmazonReturn;
use App\Entity\FbaReturn;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

class AmazonReturnCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return AmazonReturn::class;
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
        $actions->disable(Action::BATCH_DELETE, Action::NEW, Action::DELETE, Action::EDIT);
        return $actions;
    }

    public function getDefautOrder(): array
    {
        return ['returnDate' => "DESC"];
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            DateField::new('returnDate'),
            TextField::new('marketplaceName'),
            TextField::new('orderId'),
            TextField::new('sku'),
            NumberField::new('quantity'),
            NumberField::new('statusIntegration'),
            TextField::new('licensePlateNumber'),
            TextField::new('detailedDisposition'),
            TextField::new('saleReturnDocument'),
            ArrayField::new('logs')->setTemplatePath('admin/fields/orders/logs.html.twig')->onlyOnDetail(),
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('returnDate', "Posted date"));
            
    }
}
