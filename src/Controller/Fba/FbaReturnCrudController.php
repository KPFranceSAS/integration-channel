<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\FbaReturn;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;

class FbaReturnCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return FbaReturn::class;
    }



    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_AMAZON');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->disable(Action::BATCH_DELETE, Action::NEW, Action::DELETE, Action::EDIT);
        return $actions;
    }

    public function getDefautOrder(): array
    {
        return ['postedDate' => "DESC"];
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('marketplaceName'),
            TextField::new('amazonOrderId'),
            TextField::new('skuProduct'),
            DateField::new('postedDate'),
            TextField::new('statusLitteral'),
            TextField::new('localizationLitteral'),
            TextField::new('lpn'),
            TextField::new('amzProductStatus'),
            TextField::new('businessCentralDocument'),
            BooleanField::new('close')->renderAsSwitch(false),
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')
                ->canSelectMultiple(true)
                ->setChoices(
                    [
                        'Waiting for return' => FbaReturn::STATUS_WAITING_CUSTOMER,
                        'Returned to FBA Unsellable' => FbaReturn::STATUS_RETURN_TO_FBA_NOTSELLABLE,
                        'Reintegrated to sale' => FbaReturn::STATUS_RETURN_TO_SALE,
                        'Return in Biarritz' => FbaReturn::STATUS_RETURN_TO_BIARRITZ,
                        'Receipted in La Roca'=> FbaReturn::STATUS_RETURN_TO_LAROCA,
                        'Sent to La Roca' => FbaReturn::STATUS_SENT_TO_LAROCA,
                        'Reimbursed by fba' => FbaReturn::STATUS_REIMBURSED_BY_FBA,
                   ]
                ))
            ->add(DateTimeFilter::new('postedDate', "Posted date"))
            ->add(BooleanFilter::new('close', 'Is close'))
            ->add(ChoiceFilter::new('marketplaceName', "Marketplace")
                ->canSelectMultiple(true)
                ->setChoices(
                    [
                        'Amazon UK' => 'Amazon UK',
                        'Amazon IT'  => "Amazon Seller Central - IT",
                        'Amazon DE' => "Amazon Seller Central - DE",
                        'Amazon ES' => "Amazon Seller Central - ES",
                        'Amazon FR' => 'Amazon Seller Central - FR',
                    ]
                ))
            ->add(ChoiceFilter::new('localization')
                        ->canSelectMultiple(true)
                        ->setChoices(
                            [
                                FbaReturn::LOCALIZATION_CLIENT => FbaReturn::LOCALIZATION_CLIENT,
                                FbaReturn::LOCALIZATION_FBA => FbaReturn::LOCALIZATION_FBA,
                                FbaReturn::LOCALIZATION_FBA_REFURBISHED => FbaReturn::LOCALIZATION_FBA_REFURBISHED,
                                FbaReturn::LOCALIZATION_LAROCA => FbaReturn::LOCALIZATION_LAROCA,
                                FbaReturn::LOCALIZATION_BIARRITZ => FbaReturn::LOCALIZATION_BIARRITZ,
                           ]
                    ));   
            }
}
