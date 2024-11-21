<?php

namespace App\Form;

use App\Entity\IntegrationChannel;
use App\Entity\Job;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class JobSyncPricesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('channel', EntityType::class, [
            'class' => IntegrationChannel::class,
            'expanded' => false,
            'query_builder' => function (EntityRepository $er): QueryBuilder {
                return $er->createQueryBuilder('u')
                    ->andWhere("u.active = 1")
                    ->andWhere('u.priceSync = 1 and u.stockSync=1')
                    ->orderBy('u.name', 'ASC');
            },
            
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
            'data_class' => Job::class,
            'attr' => ["class" => 'ea-new-form', "id" => 'new-Job-form']

        ]);
    }
}
