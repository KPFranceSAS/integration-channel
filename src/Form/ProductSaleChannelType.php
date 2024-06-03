<?php

namespace App\Form;

use App\Entity\ProductSaleChannel;
use App\Form\PromotionType;
use DateTime;
use Doctrine\DBAL\Types\DateTimeType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductSaleChannelType extends AbstractType
{

    private $user;

    public function __construct(Security $scurity)
    {
        /**@var User */
        $this->user = $scurity->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


        $builder
        ->addEventListener(
            FormEvents::PRE_SET_DATA,
            $this->onPreSetData(...)
        );
    }



    public function onPreSetData(FormEvent $event): void
    {
        $productMarketplace = $event->getData();
        $enabled = $this->user->hasSaleChannel($productMarketplace->getSaleChannel());
        $form = $event->getForm();
        $form
            ->add('enabled', CheckboxType::class, ['disabled'=>!$enabled])
            ->add('price', MoneyType::class, ['currency'=>$productMarketplace->getSaleChannel()->getCurrencyCode(), 'disabled'=>!$enabled])
            ->add('availableFrom', DateTimeType::class, ['disabled'=>!$enabled])
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductSaleChannel::class,
        ]);
    }
}
