<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Entity\ProductSaleChannel;
use App\Entity\Promotion;
use App\Helper\Utils\DatetimeUtils;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

class PromotionCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Promotion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setFormOptions(['attr' => ['data-controller'=>'promotion']]);
        return $crud->setEntityPermission('ROLE_AMAZON');
    }


public function configureActions(Actions $actions): Actions
{
    $actions = parent::configureActions($actions);
    $url = $this->adminUrlGenerator->setController(ImportPricingCrudController::class)->setAction('importPromotions')->generateUrl();
    $actions->add(
        Crud::PAGE_INDEX,
        Action::new('addPromotions', 'Import promotions', 'fa fa-upload')
            ->linkToUrl($url)
            ->createAsGlobalAction()
            ->displayAsLink()
            ->addCssClass('btn btn-primary')
    );
    $actions->add(Crud::PAGE_EDIT, Action::DELETE);
    return $actions;
}


public function createEntity(string $entityFqcn)
{
    $promotion = new Promotion();
    $requestProductMarketplace = $this->getContext()->getRequest()->get('productSaleChannelId', null);
    if($requestProductMarketplace){
        $productMarketplace = $this->container
                                    ->get('doctrine')
                                    ->getManager()
                                    ->getRepository(ProductSaleChannel::class)
                                    ->find($requestProductMarketplace);
        $promotion->setProductSaleChannel($productMarketplace);                             
    }
   
    return $promotion;
}


    public function configureFields(string $pageName): iterable
    {
        return [
            BooleanField::new('active'),
            TextField::new('productName')
                ->onlyOnIndex(),
            TextField::new('saleChannelName')
                ->onlyOnIndex(),
            NumberField::new('regularPrice')
                ->onlyOnIndex(),
            NumberField::new('promotionPrice')
                ->onlyOnIndex(),
            TextField::new('promotionDescriptionType')
                ->onlyOnIndex(),
            TextField::new('promotionDescriptionFrequency')
                ->onlyOnIndex(),
            AssociationField::new("productSaleChannel")
                ->onlyOnForms(),
            DateTimeField::new('beginDate')
                ->setColumns(3),
            DateTimeField::new('endDate')
                ->setColumns(3),
            FormField::addRow(),
            IntegerField::new('priority')
                ->onlyOnForms()
                ->setFormTypeOptions(
                    [
                        'attr.min'=>0,
                    "attr.max"=>10
                    ]
                )
                ->setColumns(1),
            TextField::new('comment')
                ->setColumns(6),
            FormField::addRow(),
            ChoiceField::new('discountType')
                ->setChoices(
                    [
                        'Percentage'=> Promotion::TYPE_PERCENT,
                        'Fixed price'=>Promotion::TYPE_FIXED
                     ]
                )->onlyOnForms()
                ->setColumns(3)
                ->setFormTypeOptions(
                    [
                        'attr.data-action'=>'change->promotion#toggletype'
                    ]
                ),
            NumberField::new('percentageAmount')
                ->onlyOnForms()
                ->setColumns(3),
            NumberField::new('fixedAmount')
                ->onlyOnForms()
                ->setColumns(3),
            FormField::addRow(),
            ChoiceField::new('frequency')
                ->setChoices(
                    [
                        'Continuous'=> Promotion::FREQUENCY_CONTINUE,
                        'Week end'=> Promotion::FREQUENCY_WEEKEND,
                        'Time and day'=> Promotion::FREQUENCY_TIMETOTIME,
                    ]
                )
                ->onlyOnForms()
                ->setColumns(3)
                ->setFormTypeOptions(
                    [
                        'attr.data-action'=>'change->promotion#togglefrequency'
                    ]
                ),
            ChoiceField::new('weekDays')
                ->setChoices(array_flip(DatetimeUtils::getChoicesWeekDayName()))
                ->onlyOnForms()
                ->allowMultipleChoices(true)
                ->renderExpanded()
                ->setColumns(2),
            TimeField::new('beginHour')
                ->onlyOnForms()
                ->setColumns(1),
            TimeField::new('endHour')
                ->onlyOnForms()
                ->setColumns(1),
        ];
    }
}
