<?php

namespace App\Controller\Admin;

use App\Entity\WebOrder;
use App\Service\BusinessCentral\BusinessCentralConnector;
use App\Service\ChannelAdvisor\IntegrateOrdersChannelAdvisor;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
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
            ->setEntityLabelInSingular('Order')
            ->setEntityLabelInPlural('Orders')
            ->setDateTimeFormat('yyyy-MM-dd HH:mm:ss')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewInvoice = Action::new('downloadInvoice', 'Invoice', 'fa fa-file-invoice')
            ->displayIf(static function ($entity) {
                return $entity->haveInvoice();
            })
            ->linkToCrudAction('downloadInvoice');
        $retryIntegration = Action::new('retryIntegration', 'Retry', 'fa fa-check')
            ->displayIf(static function ($entity) {
                return $entity->needRetry();
            })
            ->linkToCrudAction('retryIntegration');
        $viewOrder = Action::new(Action::DETAIL, 'Show', 'fa fa-eye')
            ->linkToCrudAction(Action::DETAIL);

        $retryAllIntegrations = Action::new('retryAllIntegrations', 'Retry integrations', 'fa fa-check')
            ->linkToCrudAction('retryAllIntegrations');

        return $actions
            ->add(Crud::PAGE_DETAIL, $viewInvoice)
            ->add(Crud::PAGE_INDEX, $viewInvoice)
            ->add(Crud::PAGE_DETAIL, $retryIntegration)
            ->add(Crud::PAGE_INDEX, $retryIntegration)
            ->add(Crud::PAGE_INDEX, $viewOrder)
            ->addBatchAction($retryAllIntegrations)

            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE, Action::EDIT);
    }


    public function configureFilters(Filters $filters): Filters
    {
        $choiceStatuts = [
            'Error integration' => WebOrder::STATE_ERROR,
            'Order integrated'  => WebOrder::STATE_SYNC_TO_ERP,
            'Invoice integrated' => WebOrder::STATE_INVOICED,
            'Error send invoice' => WebOrder::STATE_ERROR_INVOICE,
        ];

        $choiceChannels = [
            'Amazon UK' => 'Amazon UK',
            'Amazon IT'  => "Amazon Seller Central - IT",
            'Amazon DE' => "Amazon Seller Central - DE",
            'Amazon ES' => "Amazon Seller Central - ES",
            'Amazon FR' => 'Amazon Seller Central - FR',
        ];
        return $filters
            ->add(ChoiceFilter::new('status')->canSelectMultiple(true)->setChoices($choiceStatuts))
            ->add(DateTimeFilter::new('createdAt', "Created at"))
            ->add(ChoiceFilter::new('subchannel', "Marketplace")->canSelectMultiple(true)->setChoices($choiceChannels));
    }




    public function downloadInvoice(AdminContext $context, BusinessCentralConnector $businessCentral)
    {
        $webOrder = $context->getEntity()->getInstance();
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
        if ($pageName == Crud::PAGE_INDEX) {
            return [
                TextField::new('externalNumber',  "External N°"),
                TextField::new('subchannel',  "Marketplace"),
                TextField::new('erpDocument', "Document type"),
                TextField::new('documentInErp', "Document N°"),
                IntegerField::new('status')->setTemplatePath('admin/fields/status.html.twig'),
                DateTimeField::new('createdAt'),
                DateTimeField::new('updatedAt'),
            ];
        } else {
            return [
                TextField::new('externalNumber',  "External N°"),
                TextField::new('subchannel',  "Marketplace"),
                TextField::new('erpDocument', "Document type"),
                TextField::new('documentInErp', "Document N°"),
                IntegerField::new('status')->setTemplatePath('admin/fields/status.html.twig'),
                DateTimeField::new('createdAt'),
                DateTimeField::new('updatedAt'),
                ArrayField::new('logs')->setTemplatePath('admin/fields/logs.html.twig'),
                ArrayField::new('errors')->setTemplatePath('admin/fields/errors.html.twig'),
                ArrayField::new('getOrderContent', 'Content')->setTemplatePath('admin/fields/orderContent.html.twig'),
                ArrayField::new('orderBCContent', 'BC Content')->setTemplatePath('admin/fields/orderBCContent.html.twig'),
            ];
        }
    }
}
