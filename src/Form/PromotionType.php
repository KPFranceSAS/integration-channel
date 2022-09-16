<?php

namespace App\Form;

use App\Entity\Promotion;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->addEventListener(
            FormEvents::PRE_SET_DATA,
            [$this, 'onPreSetData']
        );
    }


    public function onPreSetData(FormEvent $event): void
    {
        $promotion = $event->getData();
        $form = $event->getForm();
        $options = [
            'date_widget' => 'single_text',
            "minutes" => [0, 30]
        ];

        $form
            ->add('beginDate', DateTimeType::class,  $options)
            ->add('endDate', DateTimeType::class,  $options)
            ->add('discountType', ChoiceType::class, ['choices'=> [
                'Percentage'=> Promotion::TYPE_PERCENT,
                'Fixed price'=>Promotion::TYPE_FIXED
            ]])
            ->add('fixedAmount')
            ->add('percentageAmount')
        ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Promotion::class,
        ]);
    }
}
