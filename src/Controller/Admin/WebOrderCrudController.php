<?php

namespace App\Controller\Admin;

use App\Entity\WebOrder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class WebOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return WebOrder::class;
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Order')
            ->setEntityLabelInPlural('Orders')
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::EDIT);
    }


    public function configureFilters(Filters $filters): Filters
    {
        $choices = [
            'Error integration' => WebOrder::STATE_ERROR,
            'Order integrated'  => WebOrder::STATE_SYNC_TO_ERP,
            'Invoice integrated' => WebOrder::STATE_INVOICED,
            'Error send invoice' => WebOrder::STATE_ERROR_INVOICE,
        ];

        return $filters
            ->add(ChoiceFilter::new('status')->canSelectMultiple(true)->setChoices($choices))
            ->add('createdAt');
    }





    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('externalNumber'),
            TextField::new('erpDocument'),
            TextField::new('documentInErp'),
            IntegerField::new('status')->setTemplatePath('admin/fields/status.html.twig'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),
        ];
    }
}
