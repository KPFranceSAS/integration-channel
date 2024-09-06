<?php

namespace App\Controller\Configuration;

use App\Channels\FnacDarty\FnacDartyApi;
use App\Channels\FnacDarty\FnacFr\FnacFrApi;
use App\Channels\ManoMano\ManoManoFr\ManoManoFrApi;
use App\Channels\Mirakl\Boulanger\BoulangerApi;
use App\Channels\Mirakl\Decathlon\DecathlonApi;
use App\Channels\Mirakl\LeroyMerlin\LeroyMerlinApi;
use App\Channels\Mirakl\MediaMarkt\MediaMarktApi;
use App\Controller\Admin\AdminCrudController;
use App\Entity\AmazonProductType;
use App\Entity\MarketplaceCategory;
use App\Entity\ProductTypeCategorizacion;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Request;

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
        $actions->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->displayIf(static fn ($entity) => $entity && $entity->getCountProducts() == 0 && $entity->isExistInPim()!=true))->disable(Action::BATCH_DELETE);
        $actions->disable(Action::NEW);
        return $actions;
    }


    public function seeRequired(
        Request $request,
        ManagerRegistry $managerRegistry,
        DecathlonApi $decathlonApi,
        ManoManoFrApi $manomanoApi,
        MediaMarktApi $mediamarktApi,
        FnacFrApi $fnacDartyApi,
        LeroyMerlinApi $leroymerlinApi,
    ) {

        $channel=$request->get('channel');
        $category = $request->get('category');
        $fields = ${$channel."Api"}->getAllAttributesForCategory($category);
        $categoryDb=$managerRegistry->getManager()->getRepository(MarketplaceCategory::class)->findOneBy(['code'=>$category, 'marketplace'=>$channel]);

        return $this->render('admin/crud/categorization/'.$channel.'.html.twig', ['fields'=>$fields, 'channel'=> $channel, 'category'=>$categoryDb]);
    }
    



    protected function getFieldsExport(): FieldCollection
    {
        return  FieldCollection::new([
             TextField::new('pimProductType', 'Code'),
             TextField::new('pimProductLabel', 'Name'),
             IntegerField::new('countProducts', 'Nb Products'),
             BooleanField::new('existInPim', 'Exist in PIM'),
             TextField::new('amazonCategory', 'amazonProductType'),
             TextField::new('amazonEsCategory', 'amazonEs'),
             NumberField::new('nbProductAmazonEs', 'Nb Amazon Es'),
             TextField::new('amazonFrCategory', 'amazonFr'),
             NumberField::new('nbProductAmazonFr', 'Nb Amazon fr'),
             TextField::new('amazonDeCategory', 'amazonDe'),
             NumberField::new('nbProductAmazonDe', 'Nb Amazon De'),
             TextField::new('amazonUkCategory', 'amazonUk'),
             NumberField::new('nbProductAmazonUk', 'Nb Amazon Uk'),
             TextField::new('amazonItCategory', 'amazonIt'),
             NumberField::new('nbProductAmazonIt', 'Nb Amazon It'),
             TextField::new('cdiscountCategory', 'cdiscount'),
             NumberField::new('nbProductCdiscount', 'Nb cdiscount'),
             TextField::new('decathlonCategory', 'decathlon'),
             NumberField::new('nbProductDecathlon', 'Nb decathlon'),
             TextField::new('leroymerlinCategory', 'leroymerlin'),
             NumberField::new('nbProductLeroymerlin', 'Nb Leroymerlin'),
             TextField::new('boulangerCategory', 'boulanger'),
             NumberField::new('nbProductBoulanger', 'Nb boulanger'),
             TextField::new('fnacDartyCategory', 'fnacDarty'),
             NumberField::new('nbProductFnacDarty', 'Nb fnacDarty'),
             TextField::new('mediamarktCategory', 'mediamarkt'),
             NumberField::new('nbProductMediamarkt', 'Nb Mediamarkt'),
             TextField::new('manomanoCategory', 'manomano'),
             NumberField::new('nbProductManomano', 'Nb Manomano'),
             TextField::new('miraviaCategory', 'miravia'),
             NumberField::new('nbProductMiravia', 'Nb miravia'),
             TextField::new('wortenCategory', 'worten'),
             NumberField::new('nbProductWorten', 'Nb worten'),
             TextField::new('pcComponentesCategory', 'pcComponentes'),
             NumberField::new('nbProductWorten', 'Nb pcComponentes'),
         ]);
    }



    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('pimProductType', 'Code')->setDisabled(true),
            TextField::new('pimProductLabel', 'Name')->setDisabled(true),
            IntegerField::new('countProducts', 'Nb Products')->onlyOnIndex(),
            BooleanField::new('existInPim', 'Exist in PIM')->renderAsSwitch(false)->onlyOnIndex(),
        ];

        if ($pageName == 'index') {
            $fields[] = TextField::new('amazonCategory', 'amazonProductType');
            $fields[] = TextField::new('amazonEs', 'amazonEs')->setTemplatePath('admin/fields/categorization/amazonEs.html.twig');
            $fields[] = TextField::new('amazonFr', 'amazonFr')->setTemplatePath('admin/fields/categorization/amazonFr.html.twig');
            $fields[] = TextField::new('amazonDe', 'amazonDe')->setTemplatePath('admin/fields/categorization/amazonDe.html.twig');
            $fields[] = TextField::new('amazonUk', 'amazonUk')->setTemplatePath('admin/fields/categorization/amazonUk.html.twig');
            $fields[] = TextField::new('amazonIt', 'amazonIt')->setTemplatePath('admin/fields/categorization/amazonIt.html.twig');
            $fields[] = TextField::new('boulanger', 'Boulanger')->setTemplatePath('admin/fields/categorization/boulanger.html.twig');
            $fields[] = TextField::new('carrefourEs', 'CarrefourEs')->setTemplatePath('admin/fields/categorization/carrefourEs.html.twig');
            $fields[] = TextField::new('cdiscount', 'Cdiscount')->setTemplatePath('admin/fields/categorization/cdiscount.html.twig');
            $fields[] = TextField::new('decathlon', 'Decathlon')->setTemplatePath('admin/fields/categorization/decathlon.html.twig');
            $fields[] = TextField::new('fnacDarty', 'FnacDarty')->setTemplatePath('admin/fields/categorization/fnacDarty.html.twig');
            $fields[] = TextField::new('leroymerlin', 'Leroymerlin')->setTemplatePath('admin/fields/categorization/leroymerlin.html.twig');
            $fields[] = TextField::new('mediamarkt', 'Mediamarkt')->setTemplatePath('admin/fields/categorization/mediamarkt.html.twig');
            $fields[] = TextField::new('manomano', 'Manomano')->setTemplatePath('admin/fields/categorization/manomano.html.twig');
            $fields[] = TextField::new('miravia', 'Miravia')->setTemplatePath('admin/fields/categorization/miravia.html.twig');
            $fields[] = TextField::new('worten', 'Worten')->setTemplatePath('admin/fields/categorization/worten.html.twig');
            $fields[] = TextField::new('pcComponentes', 'PcComponentes')->setTemplatePath('admin/fields/categorization/pcComponentes.html.twig');
        } else {

            $choices = [];
            $marketplaceCategories = $this->container->get('doctrine')->getManager()->getRepository(AmazonProductType::class)->findBy([], ['label'=>'ASC']);
            foreach ($marketplaceCategories as $marketplaceCategory) {
                $choices [$marketplaceCategory->getLabel()] =$marketplaceCategory->getCode();
            }

            $fields[] = ChoiceField::new('amazonCategory', 'Amazon product type')->setChoices($choices);

            $channels =[
                'amazonEs',
                'amazonFr',
                'amazonUk',
                'amazonDe',
                'amazonIt',
                'boulanger',
                'carrefourEs',
                'cdiscount',
                'decathlon',
                'fnacDarty',
                'leroymerlin',
                'manomano',
                'mediamarkt',
                "miravia",
                "pcComponentes",
                'worten'
            ];

            

            foreach ($channels as $channel) {
                $choices = [];
                $marketplaceCategories = $this->container->get('doctrine')->getManager()->getRepository(MarketplaceCategory::class)->findBy(['marketplace'=>$channel], ['path'=>'ASC']);
                foreach ($marketplaceCategories as $marketplaceCategory) {
                    $choices [$marketplaceCategory->getPath().' - ['.$marketplaceCategory->getCode().']'] =$marketplaceCategory->getCode();
                }
                $fields[] = ChoiceField::new($channel.'Category')->setChoices($choices);
                
            }


          

            
        }

        return $fields;
    }



}
