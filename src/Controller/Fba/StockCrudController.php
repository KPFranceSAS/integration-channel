<?php

namespace App\Controller\Fba;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class StockCrudController extends AdminCrudController
{
    public function getDefautOrder(): array
    {
        return ['differenceStock' => "DESC"];
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
        return $crud->setEntityPermission('ROLE_AMAZON');
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        return $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::DETAIL, ACTION::EDIT);
    }


    protected function getFieldsExport(): FieldCollection
    {
        $fields = [
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
            IntegerField::new('laRocaBusinessCentralStock', 'BC la Roca stock'),
            IntegerField::new('soldStockNotIntegrated', 'BC Amazon sales not integrated'),
            IntegerField::new('returnStockNotIntegrated', 'BC Amazon returns not integrated'),
            IntegerField::new('businessCentralStock', 'BC Amazon Stock'),
            IntegerField::new('businessCentralTotalStock', 'BC Amazon total Stock'),
            IntegerField::new('differenceStock', 'Stock Delta'),
            PercentField::new('ratioStock', 'Stock Delta %'),
        ];

        return FieldCollection::new($fields);
    }



    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('brand', 'Brand'),
            TextField::new('sku'),
            TextField::new('description', 'Product name'),
            IntegerField::new('fbaTotalStock', 'FBA stock')->setTemplatePath('admin/fields/stocks/fbaStock.html.twig'),
            IntegerField::new('businessCentralTotalStock', 'BC Amazon Stock')->setTemplatePath('admin/fields/stocks/bcStock.html.twig'),
            IntegerField::new('differenceStock', 'Stock Delta'),
            PercentField::new('ratioStock', 'Stock Delta %'),
            IntegerField::new('fbaInboundStock', 'FBA Inbound Qty')->setTemplatePath('admin/fields/stocks/inboundStock.html.twig'),
            IntegerField::new('laRocaBusinessCentralStock', 'BC la Roca stock'),
            
        ];
    }
}
