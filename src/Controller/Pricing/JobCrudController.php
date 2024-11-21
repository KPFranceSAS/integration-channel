<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Entity\ImportPricing;
use App\Entity\Job;
use App\Entity\SaleChannel;
use App\Form\ConfirmImportPricingFormType;
use App\Form\ImportPricingFormType;
use App\Form\JobFormType;
use App\Form\JobSyncPricesFormType;
use App\Form\JobSyncProductsFormType;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use function Symfony\Component\String\u;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class JobCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Job::class;
    }


    public function getDefautOrder(): array
    {
        return ['id' => "DESC"];
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('statusLitteral', 'Status')->hideOnForm()->setTemplatePath('admin/crud/job/jobStatus.html.twig'),
            TextField::new('jobType', 'Job type')->hideOnForm(),
            TextField::new('username')->hideOnForm(),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('startDate')->hideOnForm(),
            DateTimeField::new('endDate')->hideOnForm(),
            AssociationField::new('channel'),
        ];
    }


    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setEntityPermission('ROLE_PRICING');
        return $crud;
    }


    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DETAIL)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
            ->disable(Action::NEW)
            ->disable(Action::SAVE_AND_ADD_ANOTHER)
            ->add(
                Crud::PAGE_INDEX,
                Action::new('syncProduct', 'Sync products')
                    ->setIcon('fa fa-plus')
                    ->createAsGlobalAction()
                    ->linkToCrudAction('syncProduct')
            )
            ->add(
                Crud::PAGE_INDEX,
                Action::new('syncPrice', 'Sync prices & stock')
                    ->setIcon('fa fa-plus')
                    ->createAsGlobalAction()
                    ->linkToCrudAction('syncPrice')
            );
            
    }






    public function syncPrice(AdminContext $context, ManagerRegistry $managerRegistry)
    {
        $job = new Job();
        $job->setJobType(Job::Type_Sync_Prices);
        $form = $this->createForm(JobSyncPricesFormType::class, $job);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $jobs = $managerRegistry->getManager()->getRepository(Job::class)->findAllProcessingChannel(Job::Type_Sync_Prices, $job->getChannel());

            if(count($jobs)==0){
                $job->setUser($this->getUser());
                $this->addFlash('success', 'Your job will be launched soon');
                $managerRegistry->getManager()->persist($job);
                $managerRegistry->getManager()->flush();
            } else{
                $this->addFlash('danger', 'A job is already processing for '.$job->getChannel());
            }           
            $url = $this->container->get(AdminUrlGenerator::class)->setAction("index")->generateUrl();
            return $this->redirect($url);
        }
        return $this->renderForm('admin/crud/job/create.html.twig', ['form' => $form, 'job' => $job]);
    }

   


    public function syncProduct(AdminContext $context, ManagerRegistry $managerRegistry)
    {
        $job = new Job();
        $job->setJobType(Job::Type_Sync_Products);
        $form = $this->createForm(JobSyncProductsFormType::class, $job);
        $form->handleRequest($context->getRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $jobs = $managerRegistry->getManager()->getRepository(Job::class)->findAllProcessingChannel(Job::Type_Sync_Products, $job->getChannel());

            if(count($jobs)==0){
                $job->setUser($this->getUser());
                $this->addFlash('success', 'Your job will be launched soon');
                $managerRegistry->getManager()->persist($job);
                $managerRegistry->getManager()->flush();
            } else{
                $this->addFlash('danger', 'A job is already processing for '.$job->getChannel());
            }           
            $url = $this->container->get(AdminUrlGenerator::class)->setAction("index")->generateUrl();
            return $this->redirect($url);
        }
        return $this->renderForm('admin/crud/job/create.html.twig', ['form' => $form, 'job' => $job]);
    }


    
}
