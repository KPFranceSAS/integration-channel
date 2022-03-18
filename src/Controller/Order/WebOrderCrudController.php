<?php

namespace App\Controller\Order;

use App\Controller\Admin\AdminCrudController;
use App\Entity\WebOrder;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use App\Service\Integrator\IntegratorAggregator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use Symfony\Component\HttpFoundation\Response;

class WebOrderCrudController extends AdminCrudController
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


    public function getName(): string
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

        $retryIntegration = Action::new('retryIntegration', 'Retry', 'fas fa-redo')
            ->displayIf(static function ($entity) {
                return $entity->needRetry();
            })
            ->addCssClass('btn')
            ->linkToCrudAction('retryIntegration');

        $seeOriginalOrder = Action::new('checkOrderOnline', 'See online', 'fa fa-eye')
            ->addCssClass('btn')
            ->setHtmlAttributes(['target' => '_blank'])
            ->linkToUrl(static function ($entity) {
                return $entity->getUrl();
            });

        $seeTrackOrder = Action::new('trackOrder', 'Track order', 'fas fa-truck')
            ->addCssClass('btn')
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(static function ($entity) {
                return strlen($entity->getTrackingUrl()) > 0;
            })
            ->linkToUrl(static function ($entity) {
                return $entity->getUrl();
            });

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



        $viewOrderIndex = Action::new(Action::DETAIL, '', 'fa fa-eye')
            ->linkToCrudAction(Action::DETAIL);

        $retryAllIntegrationBatchs = Action::new('retryAllIntegrations', 'Retry integrations', 'fas fa-redo')
            ->addCssClass('btn btn-primary')
            ->linkToCrudAction('retryAllIntegrations');

        $exportIndex = Action::new('export', 'Export to csv')
            ->setIcon('fa fa-download')
            ->linkToCrudAction('export')
            ->setCssClass('btn btn-primary')
            ->createAsGlobalAction();



        return $actions
            ->add(Crud::PAGE_DETAIL, $viewInvoice)
            ->add(Crud::PAGE_DETAIL, $seeOriginalOrder)
            ->add(Crud::PAGE_DETAIL, $seeTrackOrder)
            ->add(Crud::PAGE_DETAIL, $retryIntegration)
            ->add(Crud::PAGE_INDEX, $viewInvoiceIndex)
            ->add(Crud::PAGE_INDEX, $exportIndex)
            ->add(Crud::PAGE_INDEX, $retryIntegrationIndex)
            ->add(Crud::PAGE_INDEX, $viewOrderIndex)
            ->addBatchAction($retryAllIntegrationBatchs)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::EDIT);
    }


    public function configureFilters(Filters $filters): Filters
    {
        $choiceStatuts = [
            WebOrder::STATE_ERROR_TEXT => WebOrder::STATE_ERROR,
            WebOrder::STATE_SYNC_TO_ERP_TEXT  => WebOrder::STATE_SYNC_TO_ERP,
            WebOrder::STATE_INVOICED_TEXT => WebOrder::STATE_INVOICED,
            WebOrder::STATE_CANCELLED_TEXT => WebOrder::STATE_CANCELLED,
        ];


        $choicesFulfiled = [
            WebOrder::FULFILLED_BY_EXTERNAL => WebOrder::FULFILLED_BY_EXTERNAL,
            WebOrder::FULFILLED_BY_SELLER  => WebOrder::FULFILLED_BY_SELLER
        ];


        $choicesWarehouse = [
            WebOrder::DEPOT_CENTRAL => WebOrder::DEPOT_CENTRAL,
            WebOrder::DEPOT_MADRID => WebOrder::DEPOT_MADRID,
            WebOrder::DEPOT_FBA_AMAZON  => WebOrder::DEPOT_FBA_AMAZON
        ];


        return $filters
            ->add(ChoiceFilter::new('status')->canSelectMultiple(true)->setChoices($choiceStatuts))
            ->add(DateTimeFilter::new('purchaseDate', "Purchase date"))
            ->add(ChoiceFilter::new('subchannel', "Marketplace")->canSelectMultiple(true)->setChoices($this->getMarketplaces()))
            ->add(ChoiceFilter::new('company', "Company")->canSelectMultiple(true)->setChoices($this->getCompanies()))
            ->add(ChoiceFilter::new('fulfilledBy')->canSelectMultiple(true)->setChoices($choicesFulfiled))
            ->add(ChoiceFilter::new('warehouse')->canSelectMultiple(true)->setChoices($choicesWarehouse));
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



    public function retryAllIntegrations(BatchActionDto $batchActionDto,  IntegratorAggregator $integratorAggregator)
    {
        $entityManager = $this->getDoctrine()->getManagerForClass($batchActionDto->getEntityFqcn());
        foreach ($batchActionDto->getEntityIds() as $id) {
            $webOrder = $entityManager->find($batchActionDto->getEntityFqcn(), $id);
            if ($webOrder->getStatus() == WebOrder::STATE_ERROR) {
                $integrator = $integratorAggregator->getIntegrator($webOrder->getChannel());
                $integrator->reIntegrateOrder($webOrder);
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



    public function retryIntegration(AdminContext $context, IntegratorAggregator $integratorAggregator)
    {
        $webOrder = $context->getEntity()->getInstance();
        $integrator = $integratorAggregator->getIntegrator($webOrder->getChannel());
        $integrator->reIntegrateOrder($webOrder);
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
            TextField::new('warehouse', "Warehouse"),
            TextField::new('getStatusLitteral', "Status")->setTemplatePath('admin/fields/status.html.twig'),
            DateTimeField::new('purchaseDate', "Purchase date"),
            DateTimeField::new('createdAt', "Created at"),
        ];

        if ($pageName == CRUD::PAGE_DETAIL) {
            $fields = array_merge(
                $fields,
                [
                    DateTimeField::new('updatedAt', "Updated at"),
                    ArrayField::new('errors')->setTemplatePath('admin/fields/errors.html.twig')->onlyOnDetail(),
                    ArrayField::new('getOrderContent', 'Content')->setTemplatePath('admin/fields/orderContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('orderBCContent', 'ERP Content')->setTemplatePath('admin/fields/orderBCContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('logs')->setTemplatePath('admin/fields/logs.html.twig')->onlyOnDetail(),
                ]
            );
        }
        return $fields;
    }
}
