<?php

namespace App\Form;

use App\Entity\WebOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangeStatusInvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('invoiceErp', TextType::class, ['required' => true])
            ->add('comments', TextType::class, ['required' => true])
            ->add('trackingUrl', TextType::class, ['required' => false])
            ->add(
                'submit',
                SubmitType::class,
                ['attr' => ['class' => 'action-saveAndReturn btn btn-primary action-save'],]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WebOrder::class,
        ]);
    }
}
