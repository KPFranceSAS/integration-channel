<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Controller\Pricing\ImportPricingCrudController;
use App\Entity\Product;
use App\Entity\SaleChannel;
use App\Form\ProductSaleChannelType;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Writer\CSV\Writer;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use function Symfony\Component\String\u;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PricingCrudController extends AdminCrudController
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
        return 'Pricing';
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->overrideTemplate('crud/edit', 'admin/crud/pricing/edit.html.twig');
        $crud->setEntityPermission('ROLE_PRICING');
        return $crud;
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
            );
            $actions->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
            return $actions;
        }



        public function configureFilters(Filters $filters): Filters
        {
            return $filters
                ->add(EntityFilter::new('brand'))
                ->add(EntityFilter::new('category'))
                ->add(TextFilter::new('sku'));
        }


    public function configureFields(string $pageName): iterable
    {
        if ($pageName==Crud::PAGE_INDEX) {
            return [
                TextField::new('sku'),
                AssociationField::new('brand'),
                AssociationField::new('category'),
                TextField::new('description', 'Product name'),
                NumberField::new('unitCost', 'Unit cost €')->setDisabled(),
            ];
        } elseif ($pageName==Crud::PAGE_NEW) {
            return [
                TextField::new('sku'),
            ];
        } elseif ($pageName==Crud::PAGE_EDIT) {
            return [
                CollectionField::new('productSaleChannels')
                    ->setEntryIsComplex(true)
                    ->renderExpanded(true)
                    ->setEntryType(ProductSaleChannelType::class)
                    ->allowAdd(false)
                    ->allowDelete(false)
                    ->setFormTypeOption('block_name', 'productsaleshannels_lists')
            ];
        } else {
            return [
                TextField::new('sku'),
                
            ];
        }
    }

    protected function createWriterArray($fields, string $filePath): Writer
    {
        $writer = WriterEntityFactory::createCSVWriter();
        $writer->setFieldDelimiter(';');
        $writer->openToFile($filePath);
        $singleRow = WriterEntityFactory::createRowFromArray($fields);
        $writer->addRow($singleRow);
        return $writer;
    }


    public function export(
        FilterFactory $filterFactory,
        AdminContext $context,
        EntityFactory $entityFactory,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $directory = $params->get('kernel.project_dir') . '/var/export/';
        $fileName = u('Export_' . $this->getName() . '_' . date('Ymd_His'))->snake() . '.csv';
        $fields = $this->getFieldsExport();

        $saleChannels = $this->container->get('doctrine')->getManager()->getRepository(SaleChannel::class)->findAll();
        $header = ['sku'];
        foreach ($saleChannels as $saleChannel) {
            $header[]=$saleChannel->getCode().'-enabled';
            $header[]=$saleChannel->getCode().'-price';
            $header[]=$saleChannel->getCode().'-promoprice';
            $header[]=$saleChannel->getCode().'-promodescription';
        }
        $writer = $this->createWriterArray($header, $directory . $fileName);

        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $results = $queryBuilder->getQuery()->getResult();

        
        foreach ($results as $result) {
            $productArray = array_fill_keys($header, null);
            $productArray['sku'] = $result->getSku();
            foreach ($result->getProductSaleChannels() as $productSaleChannel) {
                $productArray[$productSaleChannel->getSaleChannel()->getCode().'-enabled']=(int)$productSaleChannel->getEnabled();
                $productArray[$productSaleChannel->getSaleChannel()->getCode().'-price']= $productSaleChannel->getPrice() ? $productSaleChannel->getPrice()  :'';
                $promotion = $productSaleChannel->getBestPromotionForNow();
                if ($promotion) {
                    $productArray[$productSaleChannel->getSaleChannel()->getCode().'-promoprice'] =  $promotion->getPromotionPrice();
                    $productArray[$productSaleChannel->getSaleChannel()->getCode().'-promodescription'] =  $promotion->getPromotionDescriptionFrequency();
                }
            }
            $singleRowData = WriterEntityFactory::createRowFromArray($productArray);
            $writer->addRow($singleRowData);
        }
        $writer->close();
        $logger->info('Finish ');

        $response = new BinaryFileResponse($directory . $fileName);
        $response->headers->set('Content-Type', 'text/csv');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );
        return $response;
    }
}
