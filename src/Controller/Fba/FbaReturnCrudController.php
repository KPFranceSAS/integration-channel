<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\FbaReturn;
use DateTime;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

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
            TextField::new('businessCentralDocument'),
            BooleanField::new('close')->renderAsSwitch(false),
        ];
    }
}
