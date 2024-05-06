<?php

namespace App\Controller\Pricing;

use App\Controller\Admin\AdminCrudController;
use App\Controller\Pricing\ImportPricingCrudController;
use App\Entity\Product;
use App\Entity\Promotion;
use App\Entity\SaleChannel;
use App\Filter\ProductFilter;
use App\Filter\SaleChannelFilter;
use App\Form\MultiPromotionType;
use App\Helper\FormClass\MultiPromotion;
use App\Helper\Utils\DatetimeUtils;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Exception\InsufficientEntityPermissionException;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PromotionCrudController extends AdminCrudController
{
    public static function getEntityFqcn(): string
    {
        return Promotion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setFormOptions(['attr' => ['data-controller'=>'promotion']]);
        $crud->setEntityPermission('ROLE_PRICING');
        $crud->setPageTitle('edit', fn (Promotion $promotion) => 'Edit Promotion ' .$promotion->getProduct()->getSku().' - '.$promotion->getProduct()->getDescription().' on '.$promotion->getSaleChannel()->getName());
        return $crud;
    }


    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $url = $this->adminUrlGenerator->setController(ImportPricingCrudController::class)->setAction('importPromotions')->generateUrl();
        $actions->add(
            Crud::PAGE_INDEX,
            Action::new('addPromotions', 'Import promotions', 'fa fa-upload')
                ->linkToUrl($url)
                ->createAsGlobalAction()
                ->displayAsLink()
                ->addCssClass('btn btn-primary')
        );

        $urlNew = $this->adminUrlGenerator->setController(self::class)->setAction('addMultiPromotions')->generateUrl();
        $actions->add(
            Crud::PAGE_INDEX,
            Action::new('addMultiPromotions', 'Add promotions', 'fa fa-plus')
            ->linkToUrl($urlNew)
                ->createAsGlobalAction()
                ->displayAsLink()
                ->addCssClass('btn btn-primary')
        );
        $actions->add(Crud::PAGE_EDIT, Action::DELETE);
        $actions->remove(Crud::PAGE_INDEX, Action::NEW);
        return $actions;
    }




    public function addMultiPromotions(AdminContext $context, ValidatorInterface $validator, ManagerRegistry $managerRegistry)
    {
       
        if (!$this->isGranted(Permission::EA_EXECUTE_ACTION, ['action' => Action::NEW, 'entity' => null])) {
            throw new ForbiddenActionException($context);
        }

        if (!$context->getEntity()->isAccessible()) {
            throw new InsufficientEntityPermissionException($context);
        }

        $manager = $managerRegistry->getManager();
        $multipromotion = new MultiPromotion();
        $requestChannelId = $this->getContext()->getRequest()->get('saleChannelId', null);
        
        if ($requestChannelId) {
            $multipromotion->saleChannels[] = $manager->getRepository(SaleChannel::class)->find($requestChannelId);
        }
        
        $requestProductId = $this->getContext()->getRequest()->get('productId', null);
        if($requestProductId) {
            $multipromotion->products[] = $manager->getRepository(Product::class)->find($requestProductId);
        }
        $errors = [];
        $newForm = $this->createForm(MultiPromotionType::class, $multipromotion);
        $newForm->handleRequest($context->getRequest());
        if ($newForm->isSubmitted() && $newForm->isValid()) {
                
            $promotions=$multipromotion->generatePromotions();
                
            foreach($promotions as $promotion) {
                $errorPromotions = $validator->validate($promotion);
                if($errorPromotions->count()>0) {
                    foreach($errorPromotions as $errorPromotion) {
                        $errors[] = $errorPromotion->getMessage();
                    }
                }
                    
            }
            if(count($errors)==0) {
                foreach($promotions as $promotion) {
                    $manager->persist($promotion);
                }
                $manager->flush();
                $this->addFlash('success', count($promotions).' have been created.');
                $url = $this->adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl();
            
                return $this->redirect($url);
            }
        }

        return $this->render('admin/crud/promotion/multi.html.twig', ['form'=>$newForm, 'errors' => $errors]);
    }







    public function configureFields(string $pageName): iterable
    {
        yield BooleanField::new('active')->renderAsSwitch(false);
        yield TextField::new('productName')->onlyOnIndex();
        yield TextField::new('saleChannelName')->onlyOnIndex();
        yield NumberField::new('regularPrice')->onlyOnIndex();
        yield NumberField::new('promotionPrice')->onlyOnIndex();
        yield TextField::new('promotionDescriptionType')->onlyOnIndex();
        yield TextField::new('promotionDescriptionFrequency')->onlyOnIndex();
        yield DateTimeField::new('beginDate')->setColumns(3);
        yield DateTimeField::new('endDate') ->setColumns(3);
        yield FormField::addRow();
        yield IntegerField::new('priority')
                ->setFormTypeOptions(
                    [
                        'attr.min'=>0,
                        "attr.max"=>10
                    ]
                )
                ->setColumns(1);
        yield   TextField::new('comment')->setColumns(6);
        yield   FormField::addRow();
        yield  ChoiceField::new('discountType')
                    ->setChoices(
                        [
                            'Percentage'=> Promotion::TYPE_PERCENT,
                            'Fixed price'=>Promotion::TYPE_FIXED
                        ]
                    )->onlyOnForms()
                    ->setColumns(3)
                    ->setFormTypeOptions(
                        [
                            'attr.data-action'=>'change->promotion#toggletype'
                        ]
                    );
        yield  NumberField::new('percentageAmount')
                    ->onlyOnForms()
                    ->setColumns(3);
        yield  NumberField::new('fixedAmount')
                ->onlyOnForms()
                ->setColumns(3);
        yield   BooleanField::new('overrided')
                ->renderAsSwitch(false)
                ->setHelp("Check it if you need to define a price with no consideration of unit cost");
        yield   DateTimeField::new('createdAt')->onlyOnIndex();
        yield   DateTimeField::new('updatedAt')->onlyOnIndex();
        
        yield  FormField::addRow();
        yield  ChoiceField::new('frequency')
                ->setChoices(
                    [
                        'Continuous'=> Promotion::FREQUENCY_CONTINUE,
                        'Week end'=> Promotion::FREQUENCY_WEEKEND,
                        'Time and day'=> Promotion::FREQUENCY_TIMETOTIME,
                    ]
                )
                ->onlyOnForms()
                ->setColumns(3)
                ->setFormTypeOptions(
                    [
                        'attr.data-action'=>'change->promotion#togglefrequency'
                    ]
                );
        yield  ChoiceField::new('weekDays')
                ->setChoices(array_flip(DatetimeUtils::getChoicesWeekDayName()))
                ->onlyOnForms()
                ->allowMultipleChoices(true)
                ->renderExpanded()
                ->setColumns(2);
        yield TimeField::new('beginHour')
                ->onlyOnForms()
                ->setColumns(1);
        yield  TimeField::new('endHour')
                ->onlyOnForms()
                ->setColumns(1);
    }


    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ProductFilter::new('product'))
            ->add(SaleChannelFilter::new('saleChannel'))
            ->add(BooleanFilter::new('active'))
            ->add(DateTimeFilter::new('beginDate'))
            ->add(DateTimeFilter::new('endDate'))
            ->add(DateTimeFilter::new('createdAt'))
            ->add(DateTimeFilter::new('updatedAt'))
        ;
    }
}
