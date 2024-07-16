<?php

namespace App\Controller\Order;

use App\BusinessCentral\Connector\BusinessCentralAggregator;
use App\BusinessCentral\Connector\BusinessCentralConnector;
use App\Controller\Admin\AdminCrudController;
use App\Controller\Admin\DashboardController;
use App\Entity\IntegrationChannel;
use App\Entity\WebOrder;
use App\Filter\LateOrderFilter;
use App\Form\ChangeCompleteType;
use App\Form\ChangeStatusInvoiceType;
use App\Service\Aggregator\IntegratorAggregator;
use Doctrine\Persistence\ManagerRegistry;
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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class WebOrderCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return WebOrder::class;
    }


    public function getDefautOrder(): array
    {
        return ['purchaseDate' => "DESC"];
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->overrideTemplate('crud/detail', 'admin/crud/order/detail.html.twig');
        $searchFields = ['externalNumber','channel','subchannel','company', 'customerNumber', 'orderErp','fulfilledBy','carrierService', 'warehouse','invoiceErp'];
        $crud->setSearchFields($searchFields);
        return $crud;
    }


    public function getName(): string
    {
        return "Order";
    }


    public function configureActions(Actions $actions): Actions
    {
        $viewInvoice = Action::new('downloadInvoice', 'Invoice', 'fa fa-file-invoice')
            ->displayIf(static fn($entity) => $entity->haveInvoice())
            ->addCssClass('btn btn-success')
            ->linkToCrudAction('downloadInvoice');

        $retryIntegration = Action::new('retryIntegration', 'Retry', 'fas fa-redo')
            ->displayIf(static fn($entity) => $entity->needRetry())
            ->addCssClass('btn')
            ->linkToCrudAction('retryIntegration');

        

        $seeOriginalOrder = Action::new('checkOrderOnline', 'See online', 'fa fa-eye')
            ->addCssClass('btn')
            ->setHtmlAttributes(['target' => '_blank'])
            ->linkToUrl(static fn($entity) => $entity->getUrl());

        $seeTrackOrder = Action::new('trackOrder', 'Track order', 'fas fa-truck')
            ->addCssClass('btn')
            ->setHtmlAttributes(['target' => '_blank'])
            ->displayIf(static fn($entity) => strlen((string) $entity->getTrackingUrl()) > 0)
            ->linkToUrl(static fn($entity) => $entity->getTrackingUrl());

        $viewInvoiceIndex = Action::new('downloadInvoice', '', 'fa fa-file-invoice')
            ->displayIf(static fn($entity) => $entity->haveInvoice())
            ->linkToCrudAction('downloadInvoice');

        $retryIntegrationIndex = Action::new('retryIntegration', '', 'fas fa-redo')
            ->displayIf(static fn($entity) => $entity->needRetry())
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



        $url = $this->container->get(AdminUrlGenerator::class)
            ->setDashboard(DashboardController::class)
            ->setController(static::class)
            ->set('filters', [
                'isLate' => 1
            ])
            ->setAction(Action::INDEX)
            ->generateUrl();

        $lateIndex = Action::new('late', 'Check late orders')
            ->setIcon('fas fa-hourglass-end')
            ->linkToUrl($url)
            ->setCssClass('btn btn-danger')
            ->createAsGlobalAction();

        $actions->add(Crud::PAGE_INDEX, $lateIndex);


        $actions
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

        /** @var \App\Entity\User */
        $user = $this->getUser();
        if ($user->hasRole('ROLE_ADMIN')) {
            $changeStatusToInvoiced = Action::new('changeStatusToInvoiced', 'Mark as invoiced', 'fas fa-check')
                ->displayIf(static fn($entity) => $entity->canChangeStatusToInvoiced())
                ->addCssClass('btn')
                ->linkToCrudAction('changeStatusToInvoiced');
            $actions->add(Crud::PAGE_DETAIL, $changeStatusToInvoiced);

            $changeStatusToCompleted = Action::new('changeStatusToComplete', 'Mark as complete', 'fas fa-user-check')
            ->displayIf(static fn($entity) => $entity->canChangeStatusToComplete())
            ->addCssClass('btn')
            ->linkToCrudAction('changeStatusToComplete');
            $actions->add(Crud::PAGE_DETAIL, $changeStatusToCompleted);
        }



        return $actions;
    }



    public function configureFilters(Filters $filters): Filters
    {
        $choiceStatuts = [
            'Error' => WebOrder::STATE_ERROR,
            'Sync to erp'  => WebOrder::STATE_SYNC_TO_ERP,
            'On delivery' => WebOrder::STATE_INVOICED,
            'Completed' => WebOrder::STATE_COMPLETE,
            'Cancelled' => WebOrder::STATE_CANCELLED,
        ];


        $choicesFulfiled = [
            WebOrder::FULFILLED_BY_EXTERNAL => WebOrder::FULFILLED_BY_EXTERNAL,
            WebOrder::FULFILLED_BY_SELLER  => WebOrder::FULFILLED_BY_SELLER
        ];


        $choicesCarrier = [
            WebOrder::CARRIER_ARISE => WebOrder::CARRIER_ARISE,
            WebOrder::CARRIER_DBSCHENKER  => WebOrder::CARRIER_DBSCHENKER,
            WebOrder::CARRIER_DHL  => WebOrder::CARRIER_DHL,
            WebOrder::CARRIER_FBA  => WebOrder::CARRIER_FBA,
            WebOrder::CARRIER_UPS  => WebOrder::CARRIER_UPS,
            WebOrder::CARRIER_TNT  =>  WebOrder::CARRIER_TNT,
            WebOrder::CARRIER_SENDING  =>  WebOrder::CARRIER_SENDING,
            WebOrder::CARRIER_CORREOSEXP  =>  WebOrder::CARRIER_CORREOSEXP,
        ];

        $choicesWarehouse = [
            WebOrder::DEPOT_CENTRAL => WebOrder::DEPOT_CENTRAL,
            WebOrder::DEPOT_3PLUK => WebOrder::DEPOT_3PLUK,
            WebOrder::DEPOT_LAROCA => WebOrder::DEPOT_LAROCA,
            WebOrder::DEPOT_MADRID => WebOrder::DEPOT_MADRID,
            WebOrder::DEPOT_FBA_AMAZON  => WebOrder::DEPOT_FBA_AMAZON
        ];


        return $filters
            ->add(ChoiceFilter::new('status')->canSelectMultiple(true)->setChoices($choiceStatuts))
            ->add(DateTimeFilter::new('purchaseDate', "Purchase date"))
            ->add(DateTimeFilter::new('createdAt', "Created at"))
            ->add(DateTimeFilter::new('updatedAt', "Last updated"))
            ->add(LateOrderFilter::new('isLate', 'Is late'))
            ->add(ChoiceFilter::new('channel', "Channel")->canSelectMultiple(true)->setChoices($this->getChannels()))
            ->add(ChoiceFilter::new('subchannel', "Marketplace")->canSelectMultiple(true)->setChoices($this->getMarketplaces()))
            ->add(ChoiceFilter::new('company', "Company")->canSelectMultiple(true)->setChoices($this->getCompanies()))
            ->add(ChoiceFilter::new('fulfilledBy')->canSelectMultiple(true)->setChoices($choicesFulfiled))
            ->add(ChoiceFilter::new('carrierService')->canSelectMultiple(true)->setChoices($choicesCarrier))
            ->add(ChoiceFilter::new('warehouse')->canSelectMultiple(true)->setChoices($choicesWarehouse));
    }


    public function getCompanies()
    {
        return  [
            BusinessCentralConnector::GADGET_IBERIA => BusinessCentralConnector::GADGET_IBERIA,
            BusinessCentralConnector::KIT_PERSONALIZACION_SPORT => BusinessCentralConnector::KIT_PERSONALIZACION_SPORT,
            BusinessCentralConnector::KP_FRANCE => BusinessCentralConnector::KP_FRANCE,
            BusinessCentralConnector::KP_UK => BusinessCentralConnector::KP_UK
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
            'Amazon FR' => "Amazon Seller Central - FR",
            'Boulanger' => "Boulanger",
            'CDiscount' => "CDiscount",
            'Darty FR' => "Darty FR",
            'Decathlon FR' => "Decathlon FR",
            'Decathlon ES' => "Decathlon ES",
            'Decathlon DE' => "Decathlon DE",
            'Decathlon IT' => "Decathlon IT",
            "Fitbitcorporate.kps.tech"=> "Fitbitcorporate.kps.tech",
            'Flashled.es' => 'Flashled.es',
            'Fnac ES' => "Fnac ES",
            'Fnac FR' => "Fnac FR",
            'Leroy Merlin FR' => "Leroy Merlin LMFR",
            'Leroy Merlin ES' => "Leroy Merlin LMES",
            'Leroy Merlin IT' => "Leroy Merlin LMIT",
            'ManoMano FR' => "ManoMano FR",
            'ManoMano ES' => "ManoMano ES",
            'ManoMano DE' => "ManoMano DE",
            'ManoMano IT' => "ManoMano IT",
            'Mediamarkt.es' => "Mediamarkt.es",
            'PcComponentes.es' => 'PcComponentes.es',
            'PcComponentes.pt' => 'PcComponentes.pt',
            'PcComponentes.fr' => 'PcComponentes.fr',
            'PcComponentes.it' => 'PcComponentes.it',
            'PcComponentes.de' => 'PcComponentes.de',
            'Miravia.es' => 'Miravia.es',
            'Minibatt.com' => 'Minibatt.com',
            'Owletbaby.es' => 'Owletbaby.es',
            'Reencle.shop' => 'Reencle.shop',
            'Uk.pax.com' => 'Uk.pax.com',
            'Worten.es' => 'Worten.es',
            'Worten.pt' => 'Worten.pt',
        ];
    }


    public function getChannels()
    {
        $channels = $this->container->get('doctrine')
                    ->getManager()
                    ->getRepository(IntegrationChannel::class)
                    ->findBy(
                        [
                        'active' => true
                     ],
                        [
                            'code'=>'ASC'
                        ]
                    );
        $channelArray=[];
        foreach ($channels as $channel) {
            $channelArray[$channel->getCode()] = $channel->getName();
        }
        return $channelArray;
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



    public function retryAllIntegrations(
        BatchActionDto $batchActionDto,
        IntegratorAggregator $integratorAggregator,
        ManagerRegistry $managerRegistry
    ) {
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


    public function changeStatusToInvoiced(
        AdminContext $context,
        ManagerRegistry $managerRegistry
    ) {
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
        return $this->render('admin/crud/order/changeStatus.html.twig', ['form' => $form, 'entity' => $webOrder]);
    }



    public function changeStatusToComplete(
        AdminContext $context,
        ManagerRegistry $managerRegistry
    ) {
        $webOrder = $context->getEntity()->getInstance();
        $form = $this->createForm(ChangeCompleteType::class, $webOrder);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $webOrder->cleanErrors();
            $webOrder->setStatus(WebOrder::STATE_COMPLETE);
            $webOrder->addLog('Marked as complete by ' . $user->getUserIdentifier() . ' : ' . $webOrder->comments, 'info', $user->getUserIdentifier());
            $this->addFlash('success', "Web Order " . $webOrder->getExternalNumber() . " has been marked as complete");
            $managerRegistry->getManager()->flush();
            return $this->redirect($this->adminUrlGenerator->setController(WebOrderCrudController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($webOrder->getId())
                ->generateUrl());
        }
        return $this->render('admin/crud/order/changeStatus.html.twig', ['form' => $form, 'entity' => $webOrder]);
    }





    public function retryIntegration(
        AdminContext $context,
        IntegratorAggregator $integratorAggregator
    ) {
        $webOrder = $context->getEntity()->getInstance();
        $integrator = $integratorAggregator->getIntegrator($webOrder->getChannel());
        $integrator->reIntegrateOrder($webOrder);
        if ($webOrder->getStatus() == WebOrder::STATE_SYNC_TO_ERP) {
            $this->addFlash('success', "Web Order " . $webOrder->getExternalNumber() . " has been synced with ERP");
        } else {
            $this->addFlash('danger', "Web Order " . $webOrder->getExternalNumber() . " can't be synced with ERP " . $webOrder->getOrderErrors());
        }
        return $this->redirect($this->adminUrlGenerator->setController(self::class)->setAction(Crud::PAGE_DETAIL)->setEntityId($webOrder->getId)->generateUrl());
    }


    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('externalNumber', "External N°"),
            TextField::new('channel', "Channel"),
            TextField::new('subchannel', "Marketplace"),
            TextField::new('company', "Company"),
            TextField::new('customerNumber', "Customer"),
            TextField::new('documentInErp', "Document N°"),
            TextField::new('fulfilledBy', "Fulfillement"),
            TextField::new('carrierService', "Carrier"),
            TextField::new('warehouse', "Warehouse"),
            TextField::new('getStatusLitteral', "Status")->setTemplatePath('admin/fields/orders/status.html.twig'),
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
                    ArrayField::new('errors')->setTemplatePath('admin/fields/orders/errors.html.twig')->onlyOnDetail(),
                    ArrayField::new('orderLinesContent', 'Order lines')->setTemplatePath('admin/fields/marketplaces/orderContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerShippingContent', 'Shipping Address')->setTemplatePath('admin/fields/marketplaces/shippingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerBillingContent', 'Billing Address')->setTemplatePath('admin/fields/marketplaces/billingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerShippingBCContent', 'Shipping Address')->setTemplatePath('admin/fields/businessCentral/shippingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('headerBillingBCContent', 'Billing Address')->setTemplatePath('admin/fields/businessCentral/billingContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('orderLinesBCContent', 'Order lines')->setTemplatePath('admin/fields/businessCentral/orderContent.html.twig')->onlyOnDetail(),
                    ArrayField::new('logs')->setTemplatePath('admin/fields/orders/logs.html.twig')->onlyOnDetail(),
                ]
            );
        }
        return $fields;
    }
}
