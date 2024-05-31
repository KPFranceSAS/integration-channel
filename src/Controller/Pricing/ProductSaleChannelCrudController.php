<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Entity\ProductSaleChannel;
use App\Filter\BrandFilter;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;

class ProductSaleChannelCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductSaleChannel::class;
    }




    public function configureFields(string $pageName): iterable
    {
            return [
                AssociationField::new('product')->setDisabled(),
                AssociationField::new('saleChannel')->setDisabled(),
                BooleanField::new('enabled'),
                NumberField::new('price', 'Regular price'),
                TextField::new('discountPrice', 'Discount price')->onlyOnIndex(),
                AssociationField::new('promotions')->onlyOnIndex(),
                DateTimeField::new('updatedAt')->onlyOnIndex(),
            ];
        
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('product'))
            ->add(BrandFilter::new('brand'))
            ->add(BooleanFilter::new('enabled', 'Enabled'))
            ->add(EntityFilter::new('saleChannel')->canSelectMultiple(true))
            ;
           
    }




    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = $this->entityRepository->createQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->leftJoin('entity.product', 'entity_product')
        ->leftJoin('entity.saleChannel', 'entity_salechannel')
        ->leftJoin('entity_salechannel.integrationChannel', 'entity_integrationChannel')
        ->andWhere('entity_product.active = 1')
        ->andWhere('entity_integrationChannel.active = 1')
        ;
        return $qb;
    }


    public function getName()
    {
        return "Product on channel";
    }

    public function getPluralName()
    {
        return "Products on channels";
    }


   
    public function configureActions(Actions $actions): Actions
    {    
        $actions = parent::configureActions($actions);



        $url = $this->adminUrlGenerator->setController(ImportPricingCrudController::class)->setAction('importPricings')->generateUrl();
        $actions->add(
            Crud::PAGE_INDEX,
            Action::new('addPricings', 'Import pricings', 'fa fa-upload')
                ->linkToUrl($url)
                ->createAsGlobalAction()
                ->displayAsLink()
                ->addCssClass('btn btn-primary')
        )->add(
            Crud::PAGE_EDIT,
            Action::new('PromotionCrudController', 'Add promotion', 'fa fa-plus')
                ->linkToUrl(function (ProductSaleChannel $productSaleChannel) {
                    return $this->adminUrlGenerator->setController(PromotionCrudController::class)
                             ->setAction('addMultiPromotions')
                             ->set('entityId', null)
                             ->set('productId', $productSaleChannel->getProduct()->getId())
                             ->set('saleChannelId', $productSaleChannel->getSaleChannel()->getId())
                             ->set('sort', null)
                     ->generateUrl();
                })
                ->displayAsLink()
                ->addCssClass('btn btn-primary')
        )
        
        
        ->add(  Crud::PAGE_INDEX, Action::new('seePromotion', false, 'fas fa-percentage')
        ->linkToUrl(function (ProductSaleChannel $product):string {
            return $this->generateUrl('admin', [
                'crudControllerFqcn' => PromotionCrudController::class,
                'crudAction' => 'index',
                'filters' => [
                    "product" => [
                        "comparison" => "=",
                        "value" => $product->getProduct()->getId()
                    ],
                    "saleChannel" => [
                        "comparison" => "=",
                        "value" => $product->getSaleChannel()->getId()
                    ],
                ]
            ]);
        }))
        ->add(
            Crud::PAGE_INDEX,
            Action::new('addPromotion', false, 'fa fa-plus')
                ->linkToUrl(function (ProductSaleChannel $productSaleChannel) {
                    return $this->adminUrlGenerator->setController(PromotionCrudController::class)
                             ->setAction('addMultiPromotions')
                             ->set('entityId', null)
                             ->set('productId', $productSaleChannel->getProduct()->getId())
                             ->set('saleChannelId', $productSaleChannel->getSaleChannel()->getId())
                             ->set('sort', null)
                     ->generateUrl();
                })        )
        
        ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE)
        ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-pencil')->setLabel(false));



        return $actions;

    }

}
