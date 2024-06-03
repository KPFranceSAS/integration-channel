<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\MarketplaceCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;

class MarketplaceCategoryCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return MarketplaceCategory::class;
    }


    public function getDefautOrder(): array
    {
        return ['marketplace' => 'ASC', 'path' => "ASC"];
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_ADMIN');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->disable(Action::NEW, Action::BATCH_DELETE, Action::DELETE);
        return $actions;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('marketplace'),
            TextField::new('code'),
            TextField::new('path')->renderAsHtml(true),
        ];
    }


    public function configureFilters(Filters $filters): Filters
    {
        $channels =[
                    'amazonEs' => 'amazonEs',
                    'amazonDe' => 'amazonDe',
                    'amazonFr' => 'amazonFr',
                    'amazonUk' => 'amazonUk',
                    'amazonIt' => 'amazonIt',
                    'cdiscount' => 'cdiscount',
                    'decathlon'=>'decathlon',
                    'fnacdarty'=>'fnacDarty',
                    'boulanger'=>'boulanger',
                    'leroymerlin'=>'leroymerlin',
                    'mediamarkt'=>'mediamarkt',
                    'manomano'=>'manomano',
                    'miravia'=>'miravia',
                    
                ];


        return $filters
            ->add(ChoiceFilter::new('marketplace')->canSelectMultiple(true)->setChoices($channels));
    }
}
