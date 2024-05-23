<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Controller\Pricing\ImportPricingCrudController;
use App\Controller\Pricing\PromotionCrudController;
use App\Entity\MarketplaceCategory;
use App\Entity\Product;
use App\Entity\ProductTypeCategorizacion;
use App\Entity\SaleChannel;
use App\Filter\SaleChannelEnabledFilter;
use App\Filter\SaleChannelFilter;
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
        $exportPriceIndex = Action::new('exportPrice', 'Export prices')
        ->setIcon('fa fa-download')
        ->linkToCrudAction('exportPrice')
        ->setCssClass('btn btn-primary')
        ->createAsGlobalAction();

        $exportCategoryIndex = Action::new('exportCategory', 'Export category')
        ->setIcon('fa fa-download')
        ->linkToCrudAction('exportCategory')
        ->setCssClass('btn btn-primary')
        ->createAsGlobalAction();

    
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
                ->linkToUrl(function (Product $product) {
                    return $this->adminUrlGenerator->setController(PromotionCrudController::class)
                             ->setAction('addMultiPromotions')
                             ->set('entityId', null)
                             ->set('productId', $product->getId())
                             ->set('saleChannelId', null)
                     ->generateUrl();
                })
                ->displayAsLink()
                ->addCssClass('btn btn-primary')
        )
        ->add(Crud::PAGE_INDEX, $exportPriceIndex)
        ->add(Crud::PAGE_INDEX, $exportCategoryIndex)
        ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE)
        ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setIcon('fa fa-pencil')->setLabel(false));



        return $actions;

    }



    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('brand'))
            ->add(SaleChannelEnabledFilter::new('enabledOn', 'Enabled on'))
            ->add(TextFilter::new('sku'));
    }


    public function configureFields(string $pageName): iterable
    {
        if ($pageName==Crud::PAGE_INDEX) {
            return [
                TextField::new('sku'),
                AssociationField::new('brand'),
                TextField::new('productType'),
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


    public function exportPrice(
        FilterFactory $filterFactory,
        AdminContext $context,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $directory = $params->get('kernel.project_dir') . '/var/export/';
        $fileName = u('Export_' . $this->getName() . '_' . date('Ymd_His'))->snake() . '.csv';
        $fields = $this->getFieldsExport();
        $manager =     $this->container->get('doctrine')->getManager();
        
        $saleChannels =  $manager->getRepository(SaleChannel::class)->findAll();

        $header = ['sku', 'unitCost'];
       
        foreach ($saleChannels as $saleChannel) {
            $header[]=$saleChannel->getCode().'-enabled';
            $header[]=$saleChannel->getCode().'-price';
            $header[]=$saleChannel->getCode().'-promoprice';
            $header[]=$saleChannel->getCode().'-promodescription';
           
        }
        $writer = $this->createWriterArray($header, $directory . $fileName);

        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $results = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters)
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()->getResult();

        $batchs = [];
        foreach ($results as $result) {
            $this->addDataToFinal($result, $writer, $header);            
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


    public function addDataToFinal(Product $result, Writer $writer, array $header){
        $productArray = array_fill_keys($header, null);
        $productArray['sku'] = $result->getSku();
        $productArray['unitCost'] = $result->getUnitCost();


        foreach ($result->getProductSaleChannels() as $productSaleChannel) {
            $productArray[$productSaleChannel->getSaleChannel()->getCode().'-enabled']=(int)$productSaleChannel->getEnabled();
            $productArray[$productSaleChannel->getSaleChannel()->getCode().'-price']= $productSaleChannel->getPrice() ?: '';
            $promotion = $productSaleChannel->getBestPromotionForNow();
            if ($promotion) {
                $productArray[$productSaleChannel->getSaleChannel()->getCode().'-promoprice'] =  $promotion->getPromotionPrice();
                $productArray[$productSaleChannel->getSaleChannel()->getCode().'-promodescription'] =  $promotion->getPromotionDescriptionFrequency();
            }
            
        }
        $singleRowData = WriterEntityFactory::createRowFromArray($productArray);
        $writer->addRow($singleRowData);
    }



    public function exportCategory(
        FilterFactory $filterFactory,
        AdminContext $context,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $directory = $params->get('kernel.project_dir') . '/var/export/';
        $fileName = u('Export_category_' . $this->getName() . '_' . date('Ymd_His'))->snake() . '.csv';
        $fields = $this->getFieldsExport();
        
        $manager =     $this->container->get('doctrine')->getManager();

        $productTypes =  $manager->getRepository(ProductTypeCategorizacion::class)->findAll();

        $productIndexed = [];
        $channels =['decathlon', 'leroymerlin', 'fnacDarty', 'mediamarkt', 'manomano', 'boulanger', 'amazon', 'cdiscount'];

        foreach($productTypes as $productType){
            $indexedCategory = [];
            foreach($channels as $channel){
                $indexedCategory [$channel] = '';
                if($productType->{'get'.ucfirst($channel).'Category'}()){
                    $categoryChannel = $manager->getRepository(MarketplaceCategory::class)->findOneBy(
                        ['marketplace'=>$channel, 'code' =>$productType->{'get'.ucfirst($channel).'Category'}() ]
                    );
                    if($categoryChannel){
                        $indexedCategory [$channel] = $categoryChannel->getPath();
                    }
                }
                
            }
            $productIndexed[$productType->getPimProductType()] = $indexedCategory;
        }



        $header = ['sku', 'productType'];
       
        array_push($header, ...$channels);
        $writer = $this->createWriterArray($header, $directory . $fileName);

        $filters = $filterFactory->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $results = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters)
                ->setFirstResult(0)
                ->setMaxResults(null)
                ->getQuery()
                ->getResult();       

        foreach ($results as $result) {
            $this->addDataToFinal($result, $writer, $header, $channels, $productIndexed );

            $productArray = array_fill_keys($header, null);
            $productArray['sku'] = $result->getSku();
            $productArray['productType'] = $result->getProductType();

            foreach($channels as $channel){
                if(array_key_exists($result->getProductType(), $productIndexed)){
                    $productArray[$channel] = $productIndexed[$result->getProductType()][$channel];
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
