<?php

namespace App\Form;

use App\Entity\ImportPricing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ImportPricingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
           
            ->add('uploadedFile', FileType::class, [
                    'label' => 'File to import (Xls or csv document)',
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new File([
                            'maxSize' => '1024k',
                            'mimeTypes' => [
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'text/plain'
                            ],
                            'mimeTypesMessage' => 'Please upload a valid Xls or csv document. You provide a document of mime type {{ type }}',
                        ])
                    ],
                ])
                ->add(
                    'submit',
                    SubmitType::class,
                    ['attr' => ['class' => 'action-saveAndReturn btn btn-primary action-save'],]
                );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImportPricing::class,
            'attr' => ["class" => 'ea-new-form', "id" => 'new-ImportPricing-form']

        ]);
    }
}
