<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Controller\Admin\DashboardController;
use App\Entity\Product;
use App\Filter\NeedToAlertFilter;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class StockCrudController extends AdminCrudController
{
    public function getDefautOrder(): array
    {
        return ['fbaTotalStock' => "DESC"];
    }


    public static function getEntityFqcn(): string
    {
        return Product::class;
    }


    public function getName(): string
    {
        return 'Stock';
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setPaginatorPageSize(500);
        return $crud->setEntityPermission('ROLE_AMAZON');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        $url = $this->container->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(get_class($this))
            ->set('filters', [
                "stockAlertEu" => 1,
            ])
            ->setAction(Action::INDEX)
            ->generateUrl();

        $replenishmentEu = Action::new('replenishmentEu', 'EU restock')
            ->setIcon('fas fa-truck-loading')
            ->linkToUrl($url)
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction();

        $actions->add(Crud::PAGE_INDEX, $replenishmentEu);

        $url = $this->container->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(get_class($this))
            ->set('filters', [
                "stockAlertUk" => 1,
            ])
            ->setAction(Action::INDEX)
            ->generateUrl();

        $replenishmentUk = Action::new('replenishmentUk', 'Uk restock')
            ->setIcon('fas fa-truck-loading')
            ->linkToUrl($url)
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction();

        $actions->add(Crud::PAGE_INDEX, $replenishmentUk);

        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL);
    }


    protected function getFieldsExport(): FieldCollection
    {
        $fields = [
            TextField::new('brand', 'Brand'),
            TextField::new('sku'),
            TextField::new('description', 'Product name'),
            IntegerField::new('fbaTotalStock', 'FBA Warehouse Qty'),
            IntegerField::new('fbaSellableStock', 'FBA Sellable Qty'),
            IntegerField::new('fbaUnsellableStock', 'FBA Unsellable Qty'),
            IntegerField::new('fbaReservedStock', 'FBA Reserved Qty'),
            IntegerField::new('fbaInboundStock', 'FBA Inbound Qty'),
            IntegerField::new('fbaInboundWorkingStock', 'FBA Inbound Working Qty'),
            IntegerField::new('fbaInboundShippedStock', 'FBA Inbound Shipped Qty'),
            IntegerField::new('fbaInboundReceivingStock', 'FBA Inbound Receiving Qty'),

            IntegerField::new('fbaEuTotalStock', 'FBA EU Warehouse Qty'),
            IntegerField::new('fbaEuSellableStock', 'FBA EU Sellable Qty'),
            IntegerField::new('fbaEuUnsellableStock', 'FBA EU Unsellable Qty'),
            IntegerField::new('fbaEuReservedStock', 'FBA EU Reserved Qty'),
            IntegerField::new('fbaEuInboundStock', 'FBA EU Inbound Qty'),
            IntegerField::new('fbaEuInboundWorkingStock', 'FBA EU Inbound Working Qty'),
            IntegerField::new('fbaEuInboundShippedStock', 'FBA EU Inbound Shipped Qty'),
            IntegerField::new('fbaEuInboundReceivingStock', 'FBA EU Inbound Receiving Qty'),
            IntegerField::new('fbaEuTotalStock', 'FBA EU Warehouse Qty'),

            IntegerField::new('fbaUkSellableStock', 'FBA UK Sellable Qty'),
            IntegerField::new('fbaUkUnsellableStock', 'FBA UK Unsellable Qty'),
            IntegerField::new('fbaUkReservedStock', 'FBA UK Reserved Qty'),
            IntegerField::new('fbaUkInboundStock', 'FBA UK Inbound Qty'),
            IntegerField::new('fbaUkInboundWorkingStock', 'FBA UK Inbound Working Qty'),
            IntegerField::new('fbaUkInboundShippedStock', 'FBA UK Inbound Shipped Qty'),
            IntegerField::new('fbaUkInboundReceivingStock', 'FBA UK Inbound Receiving Qty'),
            IntegerField::new('laRocaBusinessCentralStock', 'BC la Roca stock'),
            IntegerField::new('soldStockNotIntegrated', 'BC Amazon sales not integrated'),
            IntegerField::new('returnStockNotIntegrated', 'BC Amazon returns not integrated'),
            IntegerField::new('businessCentralStock', 'BC Amazon Stock'),
            IntegerField::new('businessCentralTotalStock', 'BC Amazon total Stock'),
            IntegerField::new('differenceStock', 'Stock Delta'),
            PercentField::new('ratioStock', 'Stock Delta %')
        ];

        return FieldCollection::new($fields);
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('brand'))
            ->add(EntityFilter::new('category'))
            ->add(TextFilter::new('sku'))
            ->add(NumericFilter::new('fbaEuTotalStock', 'FBA Eu stock'))
            ->add(NumericFilter::new('fbaUkTotalStock', 'FBA Uk stock'))
            ->add(NumericFilter::new('fbaTotalStock', 'FBA stock'))
            ->add(NumericFilter::new('businessCentralTotalStock', 'BC Amazon Stock'))
            ->add(NumericFilter::new('differenceStock', 'Stock Delta'))
            ->add(NeedToAlertFilter::new('stockAlertEu', 'Need to sent stock EU')->setMarketplace('Eu'))
            ->add(NeedToAlertFilter::new('stockAlertUk', 'Need to sent stock Uk')->setMarketplace('Uk'))
            ->add(NumericFilter::new('laRocaBusinessCentralStock', 'BC la Roca stock'));
    }


    public function configureFields(string $pageName): iterable
    {
        if($pageName == Crud::PAGE_EDIT){
            return [
                TextField::new('sku')->setDisabled(),
                TextField::new('description', 'Product name')->setDisabled(),
                TextField::new('asin')->setDisabled(),
                TextField::new('fnsku')->setDisabled(),
                NumberField::new('unitCost', 'Unit cost €')->setDisabled(),
                IntegerField::new('minQtyFbaEu', 'Min FBA Eu'),
                IntegerField::new('minQtyFbaUk', 'Min FBA Uk'),
            ];
        }

        return [
            TextField::new('brand', 'Brand'),
            TextField::new('category', 'Category'),
            TextField::new('sku'),
            TextField::new('description', 'Product name'),
            NumberField::new('unitCost', 'Unit cost €')->setDisabled(),
            IntegerField::new('minQtyFbaEu', 'Min FBA Eu'),
            IntegerField::new('minQtyFbaUk', 'Min FBA Uk'),
            IntegerField::new('fbaEuTotalStock', 'FBA Eu stock')->setTemplatePath('admin/fields/stocks/fbaEuStock.html.twig'),
            IntegerField::new('fbaUkTotalStock', 'FBA Uk stock')->setTemplatePath('admin/fields/stocks/fbaUkStock.html.twig'),
            IntegerField::new('fbaTotalStock', 'FBA stock')->setTemplatePath('admin/fields/stocks/fbaStock.html.twig'),
            IntegerField::new('businessCentralTotalStock', 'BC Amazon Stock')->setTemplatePath('admin/fields/stocks/bcStock.html.twig'),
            IntegerField::new('differenceStock', 'Stock Delta')->setTemplatePath('admin/fields/stocks/deltaStock.html.twig'),
            IntegerField::new('fbaInboundStock', 'FBA Inbound Qty')->setTemplatePath('admin/fields/stocks/inboundStock.html.twig'),
            IntegerField::new('laRocaBusinessCentralStock', 'BC la Roca stock'),
        ];
    }
}
