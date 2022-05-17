<?php

namespace App\Controller\Order;

use App\Controller\Admin\AdminCrudController;
use App\Controller\Admin\DashboardController;
use App\Entity\WebOrder;
use App\Form\ChangeStatusInvoiceType;
use App\Helper\BusinessCentral\Connector\BusinessCentralConnector;
use App\Service\Aggregator\IntegratorAggregator;
use App\Helper\Utils\DatetimeUtils;
use App\Service\BusinessCentral\BusinessCentralAggregator;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Illuminate\Support\Manager;
use Symfony\Component\HttpFoundation\Response;

class WebOrderCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return WebOrder::class;
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->overrideTemplate('crud/detail', 'admin/crud/order.html.twig');

        return $crud;
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
            ->addCssClass('btn btn-success')
            ->linkToCrudAction('downloadInvoice');

        $retryIntegration = Action::new('retryIntegration', 'Retry', 'fas fa-redo')
            ->displayIf(static function ($entity) {
                return $entity->needRetry();
            })
            ->addCssClass('btn')
            ->linkToCrudAction('retryIntegration');

        $changeStatusToInvoiced = Action::new('changeStatusToInvoiced', 'Mark as invoiced', 'fas fa-check')
            ->displayIf(static function ($entity) {
                return $entity->canChangeStatusToInvoiced();
            })
            ->addCssClass('btn')
            ->linkToCrudAction('changeStatusToInvoiced');


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
                return $entity->getTrackingUrl();
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


        $filterDelay = $this->getFilterDelay();

        if (count($filterDelay) > 0) {
            $url = $this->container->get(AdminUrlGenerator::class)
                ->setDashboard(DashboardController::class)
                ->setController(get_class($this))
                ->set('filters', $filterDelay)
                ->setAction(Action::INDEX)
                ->generateUrl();

            $lateIndex = Action::new('late', 'Check late orders')
                ->setIcon('fas fa-hourglass-end')
                ->linkToUrl($url)
                ->setCssClass('btn btn-danger')
                ->createAsGlobalAction();

            $actions->add(Crud::PAGE_INDEX, $lateIndex);
        }


        $actions
            ->add(Crud::PAGE_DETAIL, $viewInvoice)
            ->add(Crud::PAGE_DETAIL, $seeOriginalOrder)
            ->add(Crud::PAGE_DETAIL, $seeTrackOrder)
            ->add(Crud::PAGE_DETAIL, $retryIntegration)
            ->add(Crud::PAGE_DETAIL, $changeStatusToInvoiced)

            ->add(Crud::PAGE_INDEX, $viewInvoiceIndex)
            ->add(Crud::PAGE_INDEX, $exportIndex)
            ->add(Crud::PAGE_INDEX, $retryIntegrationIndex)
            ->add(Crud::PAGE_INDEX, $viewOrderIndex)
            ->addBatchAction($retryAllIntegrationBatchs)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::EDIT);

        return $actions;
    }


    protected function getFilterDelay()
    {
        return [];
    }


    protected function getFilterDelayDelivery()
    {

        $dateTime = DatetimeUtils::getDateOutOfDelayBusinessDaysFrom(30);

        return [
            "status" => [
                "comparison" => "=",
                "value" => [
                    WebOrder::STATE_SYNC_TO_ERP
                ]
            ],
            "purchaseDate" => [
                "comparison" => "<",
                "value" => $dateTime->format('Y-m-d') . 'T' . $dateTime->format('H:i'),
                "value2" => "",
            ]

        ];
    }


    protected function getFilterDelayNoDelivery()
    {

        $dateTime = DatetimeUtils::getDateOutOfDelay(24);

        return [
            "status" => [
                "comparison" => "=",
                "value" => [
                    WebOrder::STATE_SYNC_TO_ERP
                ]
            ],
            "createdAt" => [
                "comparison" => "<",
                "value" => $dateTime->format('Y-m-d') . 'T' . $dateTime->format('H:i'),
                "value2" => "",
            ]

        ];
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
            WebOrder::DEPOT_LAROCA => WebOrder::DEPOT_LAROCA,
            WebOrder::DEPOT_MADRID => WebOrder::DEPOT_MADRID,
            WebOrder::DEPOT_FBA_AMAZON  => WebOrder::DEPOT_FBA_AMAZON
        ];


        return $filters
            ->add(ChoiceFilter::new('status')->canSelectMultiple(true)->setChoices($choiceStatuts))
            ->add(DateTimeFilter::new('purchaseDate', "Purchase date"))
            ->add(DateTimeFilter::new('createdAt', "Created at"))
            ->add(DateTimeFilter::new('updatedAt', "Last updated"))
            ->add(ChoiceFilter::new('channel', "Channel")->canSelectMultiple(true)->setChoices($this->getChannels()))
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


    public function getChannels()
    {
        return [
            WebOrder::CHANNEL_ALIEXPRESS => WebOrder::CHANNEL_ALIEXPRESS,
            WebOrder::CHANNEL_FITBITEXPRESS => WebOrder::CHANNEL_FITBITEXPRESS,
            WebOrder::CHANNEL_CHANNELADVISOR => WebOrder::CHANNEL_CHANNELADVISOR,
            WebOrder::CHANNEL_OWLETCARE  => WebOrder::CHANNEL_OWLETCARE
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



    public function retryAllIntegrations(BatchActionDto $batchActionDto,  IntegratorAggregator $integratorAggregator, ManagerRegistry $managerRegistry)
    {
        $entityManager = $managerRegistry->getManagerForClass($batchActionDto->getEntityFqcn());
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


    public function changeStatusToInvoiced(AdminContext $context, ManagerRegistry $managerRegistry)
    {
        $webOrder = $context->getEntity()->getInstance();
        $form = $this->createForm(ChangeStatusInvoiceType::class, $webOrder);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $webOrder->cleanErrors();
            $webOrder->setErpDocument(WebOrder::DOCUMENT_INVOICE);
            $webOrder->setStatus(WebOrder::STATE_INVOICED);
            $webOrder->addLog('Marked as invoiced by ' . $user->getUserIdentifier() . ' : ' . $webOrder->comments, 'info', $user->getUserIdentifier());
            $this->addFlash('success', "Web Order " . $webOrder->getExternalNumber() . " has been marked as invoiced");
            $managerRegistry->getManager()->flush();
            return $this->redirect($this->adminUrlGenerator->setController(WebOrderCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($webOrder->getId())
                ->generateUrl());
        }
        return $this->renderForm('admin/crud/changeStatus.html.twig', ['form' => $form, 'entity' => $webOrder]);
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
            TextField::new('customerNumber', "Customer"),
            TextField::new('documentInErp', "Document N°"),
            TextField::new('fulfilledBy', "Fulfillement"),
            TextField::new('warehouse', "Warehouse"),
            TextField::new('getStatusLitteral', "Status")->setTemplatePath('admin/fields/status.html.twig'),
            DateTimeField::new('purchaseDate', "Purchase date"),
            DateTimeField::new('createdAt', "Created at"),
            DateTimeField::new('updatedAt', "Last update"),
            TextField::new('getLastLog', "Last message logged"),
        ];

        if ($pageName == CRUD::PAGE_DETAIL) {
            $fields = array_merge(
                $fields,
                [
                    TextField::new('erpDocument', "Document type"),
                    TextField::new('orderErp', "Order N°"),
                    TextField::new('invoiceErp', "Invoice N°"),
                    ArrayField::new('errors')->setTemplatePath('admin/fields/errors.html.twig')->onlyOnDetail(),
                    ArrayField::new('orderLinesContent', 'Order lines')->setTemplatePath('admin/fields/marketplaces/orderContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerShippingContent', 'Shipping Address')->setTemplatePath('admin/fields/marketplaces/shippingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerBillingContent', 'Billing Address')->setTemplatePath('admin/fields/marketplaces/billingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerShippingBCContent', 'Shipping Address')->setTemplatePath('admin/fields/businessCentral/shippingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerBillingBCContent', 'Billing Address')->setTemplatePath('admin/fields/businessCentral/billingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('orderLinesBCContent', 'Order lines')->setTemplatePath('admin/fields/businessCentral/orderContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('logs')->setTemplatePath('admin/fields/logs.html.twig')->onlyOnDetail(),
                ]
            );
        }
        return $fields;
    }
}
