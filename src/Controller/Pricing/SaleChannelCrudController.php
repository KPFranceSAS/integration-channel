<?php

namespace App\Controller\Pricing;

use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Controller\Admin\AdminCrudController;
use App\Entity\IntegrationChannel;
use App\Entity\SaleChannel;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SaleChannelCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return SaleChannel::class;
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setEntityPermission('ROLE_PRICING');
        return $crud;
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->setPermission(Action::NEW, 'ROLE_ADMIN');
        $actions->setPermission(Action::DELETE, 'ROLE_ADMIN');
        $actions->setPermission(Action::BATCH_DELETE, 'ROLE_ADMIN');
        $actions->setPermission(Action::EDIT, 'ROLE_ADMIN');

        return $actions;
    }




    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('code'),
            TextField::new('name'),
            TextField::new('codePim'),
            ChoiceField::new('countryCode', 'Country')->setChoices([
                'Belgium' => 'BE',
                'France' => 'FR',
                'Germany' => 'DE',
                'Italy' => 'IT',
                'Portugal' => 'PT',
                'Spain' => 'ES',
                'United Kingdom' => 'GB',
            ]),
            ChoiceField::new('currencyCode', 'Currency')->setChoices([
                'EUR' => 'EUR',
                'GBP' => 'GBP'
            ]),
            ChoiceField::new('company', 'Company')->setChoices([
                BusinessCentralConnector::GADGET_IBERIA => BusinessCentralConnector::GADGET_IBERIA,
                BusinessCentralConnector::KIT_PERSONALIZACION_SPORT => BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
                BusinessCentralConnector::KP_FRANCE => BusinessCentralConnector::KP_FRANCE,
                BusinessCentralConnector::KP_UK => BusinessCentralConnector::KP_UK,
                BusinessCentralConnector::TURISPORT => BusinessCentralConnector::TURISPORT,
            ]),
            AssociationField::new('integrationChannel', 'Integration Channel'),
            ColorField::new('color', 'Color'),
        ];
    }
}
