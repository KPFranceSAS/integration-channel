<?php

namespace App\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanFilterType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'label.true' => true,
            ],
            'expanded' => true,
            'translation_domain' => 'EasyAdminBundle',
            'label_attr' => ['class' => 'radio-inline'],
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
