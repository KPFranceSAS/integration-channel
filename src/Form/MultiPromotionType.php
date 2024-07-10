<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Promotion;
use App\Entity\SaleChannel;
use App\Helper\FormClass\MultiPromotion;
use App\Helper\Utils\DatetimeUtils;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\EaFormRowType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiPromotionType extends AbstractType
{
    protected $manager;

    public function __construct(
            private Security $security,
            ManagerRegistry $managerRegistry)
    {
        $this->manager = $managerRegistry;
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
        
        $form = $event->getForm();
        

        $user = $this->security->getUser();

        $form->add('products', EntityType::class, [
            'class' => Product::class,
            'multiple' => true,
            'attr' => 
                [
                    'class'=> 'field-association',
                    'data-ea-widget' => 'ea-autocomplete'
                ], 
            'expanded' => false,
            'row_attr' => [
                'class'=>'col-md-6 mb-3'
            ]
        ])
        ->add('saleChannels', EntityType::class, [
            'class' => SaleChannel::class,
            'multiple' => true,
            'expanded' => false,
            'choices' => $user->getSaleChannels(),
            'attr' => 
                [
                    'class'=> 'field-association',
                    'data-ea-widget' => 'ea-autocomplete'
                ], 
                'row_attr' => [
                    'class'=>'col-md-6 mb-3'
                ]
        ])

             ->add('active', CheckboxType::class,  ['row_attr' => [
                'class'=>'col-md-4 mb-3'
             ], 
             'required' => false])
                    

            ->add('beginDate', DateTimeType::class, [
                'widget' => 'single_text',
                "minutes" => [0, 30],
                'row_attr' => [
                    'class'=>'col-md-4 mb-3'
                ]
            ])
            ->add('endDate', DateTimeType::class, [
                'widget' => 'single_text',
                "minutes" => [0, 30],
                'row_attr' => [
                    'class'=>'col-md-4 mb-3'
                ]
            ])
            ->add('discountType', ChoiceType::class, 
                [
                    'choices'=> [
                        'Percentage'=> Promotion::TYPE_PERCENT,
                        'Fixed price'=>Promotion::TYPE_FIXED
                    ],  
                    'attr' => [
                        'data-action'=>'change->promotions#toggletype'
                        
                    ],
                    'row_attr' => [
                        'class'=>'col-md-4 mb-3'
                    ]
            ])
            ->add('fixedAmount', NumberType::class, ['row_attr' => [
                'class'=>'col-md-4 mb-3'
            ]])
            ->add('percentageAmount', NumberType::class,  ['row_attr' => [
                'class'=>'col-md-4 mb-3'
            ]])
            ->add('overrided', CheckboxType::class,  ['row_attr' => [
                'class'=>'col-md-4 mb-3'
            ],
            'required' => false,
            "help" => 'Check it if you need to define a price with no consideration of unit cost'
            ])

           
           
            ->add('comment', TextType::class, ['row_attr' => [
                'class'=>'col-md-6 mb-3'
            ]])
            ->add('priority', IntegerType::class, 
                   ['attr' => [
                        'min'=>0,
                        "max"=>10
                   ],
                        'row_attr' => [
                            'class'=>'col-md-6 mb-3'
                        ]
                   ]
                )
                ->add('frequency', ChoiceType::class, 
                [
                    'choices'=> [
                        'Continuous'=> Promotion::FREQUENCY_CONTINUE,
                        'Week end'=> Promotion::FREQUENCY_WEEKEND,
                        'Time and day'=> Promotion::FREQUENCY_TIMETOTIME,
                    ],  
                    'attr' => ['data-action'=>'change->promotions#togglefrequency']
            ])
            ->add('weekDays', ChoiceType::class, [
                'choices' =>array_flip(DatetimeUtils::getChoicesWeekDayName()),
                'multiple' => true,
                'expanded' => true,
                'row_attr' => [
                    'class'=>'col-md-4 mb-3'
                ]
             ])
             ->add('beginHour', TimeType::class, [
                'widget' => 'single_text',
                'row_attr' => [
                    'class'=>'col-md-4 mb-3'
                ]
             ])
             ->add('endHour', TimeType::class, [
                'widget' => 'single_text',
                'row_attr' => [
                    'class'=>'col-md-4 mb-3'
                ]
             ])
            
             ->add(
                'submit',
                SubmitType::class,
                ['attr' => ['class' => 'action-saveAndReturn btn btn-primary action-save'],]
            );
            ;
              

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MultiPromotion::class,
            'attr' => ["class" => 'ea-new-form', "id" => 'new-multi_promotion-form']
        ]);
    }
}
