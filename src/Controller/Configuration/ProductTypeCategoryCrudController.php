<?php

namespace App\Controller\Configuration;

use App\Channels\FnacDarty\FnacDartyApi;
use App\Channels\ManoMano\ManoManoFr\ManoManoFrApi;
use App\Channels\Mirakl\Boulanger\BoulangerApi;
use App\Channels\Mirakl\Decathlon\DecathlonApi;
use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use App\Channels\Mirakl\MediaMarkt\MediaMarktApi;
use App\Controller\Admin\AdminCrudController;
use App\Entity\MarketplaceCategory;
use App\Entity\ProductTypeCategorizacion;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductTypeCategoryCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductTypeCategorizacion::class;
    }


    public function getDefautOrder(): array
    {
        return ['pimProductType' => "ASC"];
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_ADMIN');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->update(Crud::PAGE_INDEX, Action::DELETE, fn(Action $action) => $action->displayIf(static fn($entity) => $entity && $entity->getCountProducts() == 0))->disable(Action::BATCH_DELETE);
        $actions->disable(Action::NEW);
        return $actions;
    }



    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('pimProductType', 'Code')->setDisabled(true),
            TextField::new('pimProductLabel', 'Name')->setDisabled(true),
            IntegerField::new('countProducts', 'Nb Products')->onlyOnIndex(),
            BooleanField::new('existInPim', 'Exist in PIM')->renderAsSwitch(false)->onlyOnIndex(),
        ];

        if($pageName == 'index'){
            $fields[] = TextField::new('decathlonCategory', 'Decathlon');
            $fields[] =    TextField::new('leroymerlinCategory', 'Leroymerlin');
            $fields[] =   TextField::new('boulangerCategory', 'Boulanger');
            $fields[] =   TextField::new('fnacDartyCategory', 'FnacDarty');
            $fields[] =   TextField::new('mediamarktCategory', 'Mediamarkt');
            $fields[] =  TextField::new('manomanoCategory', 'Manomano');
        } else {

            $channels =[
                'decathlon',
                'fnacDarty',
                'boulanger',
                'leroymerlin',
                'mediamarkt',
                'manomano'
            ];

            

            foreach($channels as $channel){
                $choices = [];
                $marketplaceCategories = $this->container->get('doctrine')->getManager()->getRepository(MarketplaceCategory::class)->findBy(['marketplace'=>$channel], ['path'=>'ASC']);
                foreach($marketplaceCategories as $marketplaceCategory){
                    $choices [$marketplaceCategory->getPath().' - ['.$marketplaceCategory->getCode().']'] =$marketplaceCategory->getCode();
                }
                $fields[] = ChoiceField::new($channel.'Category')->setChoices($choices);
                
            }



            
        }

        return $fields;
    }


    
}
