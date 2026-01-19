<?php

namespace App\Form\CalendrierDesCommissions;

use App\Dto\Commission\CommissionDatesDto;
use App\Entity\Commission;
use App\Entity\CommissionTypeEvenement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommissionDatesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libellé de la commission',
                'attr' => [
                    'placeholder' => 'Saisissez le libellé de la commission',
                ],
            ])
            ->add('commission', EntityType::class, [
                'label' => 'Commission concernée',
                'class' => Commission::class,
                'choice_label' => 'libelle',
                'row_attr' => [
                    'class' => 'hidden'
                ]
            ])
            ->add('typeEvenement', EntityType::class, [
                'label' => 'Type de la commission',
                'class' => CommissionTypeEvenement::class,
                'choice_label' => 'libelle',
            ])
            ->add('dates', CollectionType::class, [
                'entry_type' => DateCommissionItemType::class,
                'entry_options' => ['label' => false],
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'label' => false,
                'attr' => [
                    'class' => 'dates-collection',
                ],
            ])
            ->add('withPeriodicite', CheckboxType::class, [
                'label' => 'Souhaitez-vous planifier cette commission sur une période donnée ?',
                'required' => false,
                'attr' => [
                    'class' => 'periodicite-checkbox',
                ],
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin de planification',
                'widget' => 'single_text',
                'required' => false,
                'row_attr' => [
                    'placeholder' => 'jj/mm/aaaa',
                    'class' => 'hidden'
                ],
            ])
            ->add('periodicite', ChoiceType::class, [
                'label' => 'Périodicité (en semaines)',
                'choices' => [
                    '1 semaine' => 1,
                    '2 semaines' => 2,
                    '3 semaines' => 3,
                    '4 semaines' => 4,
                ],
                'data' => 1,
                'required' => false,
                'row_attr' => [
                    'class' => 'hidden'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommissionDatesDto::class,
        ]);
    }
}
