<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Controller\Admin\DashboardController;
use App\Entity\AmazonFinancialEvent;
use Doctrine\ORM\Query;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class AmazonFinancialEventCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return AmazonFinancialEvent::class;
    }


    public function getDefautOrder(): array
    {
        return ['postedDate' => "DESC"];
    }



    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_AMAZON');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);


        $url = $this->container->get(AdminUrlGenerator::class)
                ->setDashboard(DashboardController::class)
                ->setController(get_class($this))
                ->set('filters', [
                    "transactionType" => [
                            "comparison" => "=",
                            "value" => [
                                'ShipmentEvent'
                            ]
                        ],
                    "amounType" => [
                        "comparison" => "=",
                        "value" => [
                            'ItemFees',
                            'ItemFeeList'
                        ]
                    ],
                    "amountDescription" => [
                        "comparison" => "=",
                        "value" => [
                            'Commission',
                            'FBAPerUnitFulfillmentFee'
                        ]
                    ]
                ])
                ->setAction(Action::INDEX)
                ->generateUrl();

        $lateIndex = Action::new('orderFees', 'Orders Fees')
                ->setIcon('fa fa-filter')
                ->linkToUrl($url)
                ->setCssClass('btn btn-secondary')
                ->createAsGlobalAction();

        $actions->add(Crud::PAGE_INDEX, $lateIndex);
        $actions->disable(Action::BATCH_DELETE, Action::DETAIL, Action::DELETE, Action::EDIT, Action::NEW);
        return $actions;
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('transactionType'),
            TextField::new('amountType'),
            TextField::new('amountDescription'),
            TextField::new('marketplaceName'),
            TextField::new('amazonOrderId'),
            TextField::new('productSku'),
            TextField::new('productBrand'),
            TextField::new('productName'),
            NumberField::new('amount', 'Amount (EUR)'),
            IntegerField::new('qtyPurchased'),
            DateTimeField::new('postedDate'),
            
        ];
    }



    protected function getFieldsExport(): FieldCollection
    {
        $fieldsCollection = $this->configureFields(Crud::PAGE_INDEX);
        $fieldsCollection[] = DateTimeField::new('getFinancialGroupStartDate', 'Group starting date');
        $fieldsCollection[] = DateTimeField::new('getFinancialGroupEndDate', 'Group ending date');
        return FieldCollection::new($fieldsCollection);
    }


    public function configureFilters(Filters $filters): Filters
    {
        $manager = $this->container->get('doctrine')->getManager();

        $transactionTypes = $manager->createQuery("SELECT DISTINCT e.transactionType 
                                                FROM App\Entity\AmazonFinancialEvent e
                                                WHERE e.transactionType IS NOT NULL")
                                    ->getResult(Query::HYDRATE_SCALAR_COLUMN);

        $amountTypes = $manager->createQuery("SELECT DISTINCT e.amountType 
                                    FROM App\Entity\AmazonFinancialEvent e
                                    WHERE e.amountType IS NOT NULL")
                        ->getResult(Query::HYDRATE_SCALAR_COLUMN);

        $amountDescriptions = $manager->createQuery("SELECT DISTINCT e.amountDescription 
                            FROM App\Entity\AmazonFinancialEvent e 
                            WHERE e.amountDescription IS NOT NULL")
                                ->getResult(Query::HYDRATE_SCALAR_COLUMN);

        $marketplaces = $manager->createQuery("SELECT DISTINCT e.marketplaceName 
                                FROM App\Entity\AmazonFinancialEvent e 
                                WHERE e.marketplaceName IS NOT NULL")
                                    ->getResult(Query::HYDRATE_SCALAR_COLUMN);

                                           
        return $filters
            ->add(DateTimeFilter::new('postedDate'))
            ->add(ChoiceFilter::new('transactionType')
                ->canSelectMultiple(true)
                ->setChoices($this->generateChoiceList($transactionTypes)))
            ->add(ChoiceFilter::new('amountType')
                ->canSelectMultiple(true)
                ->setChoices($this->generateChoiceList($amountTypes)))
            ->add(ChoiceFilter::new('amountDescription')
                ->canSelectMultiple(true)
                ->setChoices($this->generateChoiceList($amountDescriptions)))
            ->add(ChoiceFilter::new('marketplaceName')
                ->canSelectMultiple(true)
                ->setChoices($this->generateChoiceList($marketplaces)));
    }
}
