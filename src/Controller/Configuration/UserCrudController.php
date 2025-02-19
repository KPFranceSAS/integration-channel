<?php

namespace App\Controller\Configuration;

use App\Controller\Admin\AdminCrudController;
use App\Entity\User;
use App\Service\Aggregator\IntegratorAggregator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AdminCrudController
{
    protected $integratorAggregator;

    protected $passwordEncoder;

    public function __construct(AdminUrlGenerator $adminUrlGenerator, IntegratorAggregator $integratorAggregator)
    {
        $this->integratorAggregator = $integratorAggregator;
        $this->adminUrlGenerator = $adminUrlGenerator;
    }


    public function getDefautOrder(): array
    {
        return ['email' => "ASC"];
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud = parent::configureCrud($crud);
        $crud->setFormOptions(['attr' => ['data-controller'=>'user']]);
        return $crud->setEntityPermission('ROLE_ADMIN');
    }


    public static function getEntityFqcn(): string
    {
        return User::class;
    }





    public function configureFields(string $pageName): iterable
    {
        $choices = [];
        $channels = $this->integratorAggregator->getChannels();
        foreach ($channels as $channel) {
            $choices[$channel] = $channel;
        }
        $fields = [
            Field::new('email', 'Email')->setFormType(EmailType::class),
        ];
        


        if ($pageName != Crud::PAGE_INDEX) {
            $fields[] = Field::new('plainPassword', 'New password')->onlyOnForms()
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => ['label' => 'New password'],
                    'second_options' => ['label' => 'Repeat password'],
                    'required' => $pageName == Crud::PAGE_NEW
                ]);

            $fields[] = BooleanField::new('isOrderManager', 'Manage orders')->setFormTypeOptions(
                [
                    'attr.data-action'=>'change->user#togglechannels'
                ]
            );
            $fields[] = ChoiceField::new('channels', 'Channel Alerts')->setChoices($choices)->allowMultipleChoices()->setHelp("Receive alerts for orders done on this channels of integration");
            
            $fields[] = BooleanField::new('isFbaManager', 'Manage FBA');
            $fields[] = BooleanField::new('isPricingManager', 'Manage price')->setFormTypeOptions(
                [
                    'attr.data-action'=>'change->user#togglesalechannels'
                ]
            );
            $fields[] = AssociationField::new('saleChannels', 'Sale Channels')->setHelp("Manage pricings on sale channels");
            $fields[] = BooleanField::new('isAdmin', 'Manage configuration')->setFormTypeOptions(
                [
                    'attr.data-action'=>'change->user#toggleadmin'
                ]
            );
            $fields[] = BooleanField::new('isSuperAdmin', 'Manage users');
        }
        return $fields;
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);

        $this->addEncodePasswordEventListener($formBuilder);

        return $formBuilder;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);

        $this->addEncodePasswordEventListener($formBuilder);

        return $formBuilder;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setEncoder(UserPasswordHasherInterface $passwordEncoder): void
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function addEncodePasswordEventListener(FormBuilderInterface $formBuilder)
    {
        $formBuilder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var User $user */
            $user = $event->getData();
            if ($user->getPlainPassword()) {
                $user->setPassword($this->passwordEncoder->hashPassword($user, $user->getPlainPassword()));
            }
        });
    }
}
