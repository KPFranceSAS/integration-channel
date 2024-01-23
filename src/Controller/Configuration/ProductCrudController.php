<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class ProductCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function getDefautOrder(): array
    {
        return ['sku' => "ASC"];
    }

    public function getName(): string
    {
        return 'Product';
    }



    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

       

        $shippingFreeBatch = Action::new('shippingFree', 'Free Shipping')
            ->addCssClass('btn btn-primary')
            ->linkToCrudAction('shippingFree');
            $actions->addBatchAction($shippingFreeBatch);

        $shippingNotFreeBatch = Action::new('shippingNotFreeBatch', 'Paid Shipping', )
            ->addCssClass('btn btn-primary')
            ->linkToCrudAction('shippingNotFreeBatch');
            $actions->addBatchAction($shippingNotFreeBatch);
            
        return $actions->disable(Action::NEW, Action::BATCH_DELETE);
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        return $crud->setEntityPermission('ROLE_ADMIN');
    }



    public function shippingNotFreeBatch(
        BatchActionDto $batchActionDto,
        ManagerRegistry $managerRegistry
    ) {
        $entityManager = $managerRegistry->getManagerForClass($batchActionDto->getEntityFqcn());
        $updates = 0;
        foreach ($batchActionDto->getEntityIds() as $id) {
            $product = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            if ($product->getFreeShipping() !== false) {
                $product->setFreeShipping(false);
                $updates++;
            }
        }
        $entityManager->flush();
        $this->addFlash('info', $updates . " products have been updated");
        return $this->redirect($batchActionDto->getReferrerUrl());
    }



    public function shippingFree(
        BatchActionDto $batchActionDto,
        ManagerRegistry $managerRegistry
    ) {
        $entityManager = $managerRegistry->getManagerForClass($batchActionDto->getEntityFqcn());
        $updates = 0;
        foreach ($batchActionDto->getEntityIds() as $id) {
            $product = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            if ($product->getFreeShipping() !== true) {
                $product->setFreeShipping(true);
                $updates++;
            }
        }
        $entityManager->flush();
        $this->addFlash('info', $updates . " products have been updated");
        return $this->redirect($batchActionDto->getReferrerUrl());
    }





    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku')->setDisabled(),
            TextField::new('ean')->setDisabled(),
            AssociationField::new('brand'),
            AssociationField::new('category'),
            AssociationField::new('logisticClass'),
            TextField::new('description', 'Product name'),
            BooleanField::new('active')->setDisabled(),
            BooleanField::new('dangerousGood')->renderAsSwitch(true),
            BooleanField::new('freeShipping')->renderAsSwitch(true),
            DateTimeField::new('createdAt', "Created at")
        ];
    }
    

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('brand'))
            ->add(EntityFilter::new('category'))
            ->add(EntityFilter::new('logisticClass'))
            ->add(TextFilter::new('sku'))
            ->add(BooleanFilter::new('dangerousGood'))
            ->add(BooleanFilter::new('freeShipping'));
    }
}
