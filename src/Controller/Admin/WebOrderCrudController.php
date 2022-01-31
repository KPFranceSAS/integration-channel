<?php

namespace App\Controller\Admin;

use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\ChannelAdvisor\IntegrateOrdersChannelAdvisor;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\EntityFactory;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;

class WebOrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return WebOrder::class;
    }


    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->getName())
            ->setEntityLabelInPlural($this->getName() . 's')
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setDefaultSort(['purchaseDate' => 'DESC'])
            ->showEntityActionsInlined();
    }


    public function getName()
    {
        return "Order";
    }


    public function configureActions(Actions $actions): Actions
    {
        $viewInvoice = Action::new('downloadInvoice', 'Invoice', 'fa fa-file-invoice')
            ->displayIf(static function ($entity) {
                return $entity->haveInvoice();
            })
            ->addCssClass('btn')
            ->linkToCrudAction('downloadInvoice');

        $viewInvoiceIndex = Action::new('downloadInvoice', '', 'fa fa-file-invoice')
            ->displayIf(static function ($entity) {
                return $entity->haveInvoice();
            })
            ->linkToCrudAction('downloadInvoice');

        $retryIntegrationIndex = Action::new('retryIntegration', '', 'fas fa-redo')
            ->displayIf(static function ($entity) {
                return $entity->needRetry();
            })
            ->linkToCrudAction('retryIntegration');

        $retryIntegration = Action::new('retryIntegration', 'Retry', 'fas fa-redo')
            ->displayIf(static function ($entity) {
                return $entity->needRetry();
            })
            ->displayAsButton()
            ->addCssClass('btn')
            ->linkToCrudAction('retryIntegration');

        $viewOrder = Action::new(Action::DETAIL, '', 'fa fa-eye')
            ->linkToCrudAction(Action::DETAIL);

        $retryAllIntegrations = Action::new('retryAllIntegrations', 'Retry integrations', 'fas fa-redo')
            ->addCssClass('btn btn-primary')
            ->linkToCrudAction('retryAllIntegrations');

        $export = Action::new('export', 'Export to xlsx')
            ->setIcon('fa fa-download')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-primary btn-sm')
            ->createAsGlobalAction();



        return $actions
            ->add(Crud::PAGE_DETAIL, $viewInvoice)
            ->add(Crud::PAGE_INDEX, $viewInvoiceIndex)
            ->add(Crud::PAGE_INDEX, $export)
            ->add(Crud::PAGE_DETAIL, $retryIntegration)
            ->add(Crud::PAGE_INDEX, $retryIntegrationIndex)
            ->add(Crud::PAGE_INDEX, $viewOrder)
            ->addBatchAction($retryAllIntegrations)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::EDIT);
    }


    public function configureFilters(Filters $filters): Filters
    {
        $choiceStatuts = [
            WebOrder::STATE_ERROR_TEXT => WebOrder::STATE_ERROR,
            WebOrder::STATE_SYNC_TO_ERP_TEXT  => WebOrder::STATE_SYNC_TO_ERP,
            WebOrder::STATE_INVOICED_TEXT => WebOrder::STATE_INVOICED,
            WebOrder::STATE_ERROR_INVOICE_TEXT => WebOrder::STATE_ERROR_INVOICE,
        ];


        $choicesFulfiled = [
            'External' => WebOrder::FULFILLED_BY_EXTERNAL,
            'Seller'  => WebOrder::FULFILLED_BY_SELLER,
            'Mixed' => WebOrder::FULFILLED_MIXED,
        ];


        return $filters
            ->add(ChoiceFilter::new('status')->canSelectMultiple(true)->setChoices($choiceStatuts))
            ->add(DateTimeFilter::new('purchaseDate', "Purchase date"))
            ->add(ChoiceFilter::new('subchannel', "Marketplace")->canSelectMultiple(true)->setChoices($this->getMarketplaces()))
            ->add(ChoiceFilter::new('company', "Company")->canSelectMultiple(true)->setChoices($this->getCompanies()))
            ->add(ChoiceFilter::new('fulfilledBy')->canSelectMultiple(true)->setChoices($choicesFulfiled));
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::GADGET_IBERIA => BusinessCentralConnector::GADGET_IBERIA,
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT => BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
            BusinessCentralConnector::KP_FRANCE => BusinessCentralConnector::KP_FRANCE,
        ];
    }


    public function getMarketplaces()
    {
        return [
            'AliExpress' => 'AliExpress',
            'Amazon UK' => 'Amazon UK',
            'Amazon IT'  => "Amazon Seller Central - IT",
            'Amazon DE' => "Amazon Seller Central - DE",
            'Amazon ES' => "Amazon Seller Central - ES",
            'Amazon FR' => 'Amazon Seller Central - FR',
            'OwletCare' => 'Owlet Care',
        ];
    }






    public function export(AdminContext $context)
    {
        $fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $fields, $filters);
        $entities = $this->get(EntityFactory::class)->createCollection($context->getEntity(), $queryBuilder->getQuery()->getResult());
        $this->get(EntityFactory::class)->processFieldsForAll($entities, $fields);
        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToBrowser('export_orders_' . date('Ymd-His') . '.xlsx');
        $h = fopen('php://output', 'r');


        /** Create a style with the StyleBuilder */
        $style = (new StyleBuilder())
            ->setFontColor(Color::WHITE)
            ->setBackgroundColor(Color::BLUE)
            ->build();

        $cellHeaders = [];
        foreach ($fields as $field) {
            $cellHeaders[] = WriterEntityFactory::createCell($field->getLabel());
        }
        $singleRow = WriterEntityFactory::createRow($cellHeaders, $style);
        $writer->addRow($singleRow);

        $entitiesArray = $entities->getIterator();
        foreach ($entitiesArray as $entityArray) {
            $fieldsEntity = $entityArray->getFields();
            $cellDatas = [];
            foreach ($fieldsEntity as $fieldEntity) {
                $cellDatas[] = WriterEntityFactory::createCell($fieldEntity->getFormattedValue());
            }
            $singleRowData = WriterEntityFactory::createRow($cellDatas);
            $writer->addRow($singleRowData);
        }
        $writer->close();
        return new Response(stream_get_contents($h));
    }




    public function downloadInvoice(AdminContext $context, BusinessCentralAggregator $businessCentralAggregator)
    {
        $webOrder = $context->getEntity()->getInstance();
        $businessCentral = $businessCentralAggregator->getBusinessCentralConnector($webOrder->getCompany());
        $invoice = $businessCentral->getSaleInvoiceByNumber($webOrder->getInvoiceErp());
        $contentInvoice  = $businessCentral->getContentInvoicePdf($invoice['id']);
        $response = new Response();
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-type', "application/pdf");
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $webOrder->getExternalNumber() . '-' . $webOrder->getInvoiceErp() . '.pdf";');
        $response->headers->set('Content-length', strlen($contentInvoice));
        $response->sendHeaders();
        $response->setContent($contentInvoice);
        return $response;
    }



    public function retryAllIntegrations(BatchActionDto $batchActionDto, IntegrateOrdersChannelAdvisor $integrateOrdersChannelAdvisor)
    {
        $entityManager = $this->getDoctrine()->getManagerForClass($batchActionDto->getEntityFqcn());
        foreach ($batchActionDto->getEntityIds() as $id) {
            $webOrder = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            if ($webOrder->getStatus() == WebOrder::STATE_ERROR) {
                $integrateOrdersChannelAdvisor->reIntegrateOrder($webOrder);
                if ($webOrder->getStatus() == WebOrder::STATE_SYNC_TO_ERP) {
                    $this->addFlash('success', "Web Order " . $webOrder->getExternalNumber() . " has been synced with ERP");
                } else {
                    $this->addFlash('danger', "Web Order " . $webOrder->getExternalNumber() . " can't be synced with ERP " . $webOrder->getOrderErrors());
                }
            } else {
                $this->addFlash('info', "Web Order " . $webOrder->getExternalNumber() . " already synced with ERP");
            }
        }
        return $this->redirect($batchActionDto->getReferrerUrl());
    }



    public function retryIntegration(AdminContext $context, IntegrateOrdersChannelAdvisor $integrateOrdersChannelAdvisor)
    {
        $webOrder = $context->getEntity()->getInstance();
        $integrateOrdersChannelAdvisor->reIntegrateOrder($webOrder);
        if ($webOrder->getStatus() == WebOrder::STATE_SYNC_TO_ERP) {
            $this->addFlash('success', "Web Order " . $webOrder->getExternalNumber() . " has been synced with ERP");
        } else {
            $this->addFlash('danger', "Web Order " . $webOrder->getExternalNumber() . " can't be synced with ERP " . $webOrder->getOrderErrors());
        }
        return $this->redirect($context->getReferrer());
    }


    public function configureFields(string $pageName): iterable
    {

        $fields = [
            TextField::new('externalNumber',  "External N°"),
            TextField::new('channel', "Channel"),
            TextField::new('subchannel',  "Marketplace"),
            TextField::new('company', "Company"),
            TextField::new('erpDocument', "Document type"),
            TextField::new('documentInErp', "Document N°"),
            TextField::new('fulfilledBy', "Fulfillement"),
            TextField::new('getStatusLitteral', "Status")->setTemplatePath('admin/fields/status.html.twig'),
            DateTimeField::new('purchaseDate', "Purchase date"),
            DateTimeField::new('createdAt', "Created at"),
        ];

        if ($pageName == CRUD::PAGE_DETAIL) {
            $fields = array_merge(
                $fields,
                [
                    DateTimeField::new('updatedAt', "Updated at"),
                    ArrayField::new('logs')->setTemplatePath('admin/fields/logs.html.twig')->onlyOnDetail(),
                    ArrayField::new('errors')->setTemplatePath('admin/fields/errors.html.twig')->onlyOnDetail(),
                    ArrayField::new('getOrderContent', 'Content')->setTemplatePath('admin/fields/orderContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('orderBCContent', 'ERP Content')->setTemplatePath('admin/fields/orderBCContent.html.twig')->onlyOnDetail(),
                ]
            );
        }
        return $fields;
    }
}
