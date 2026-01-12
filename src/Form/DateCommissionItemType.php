<?php

namespace App\Form;

use App\Dto\Commission\DateCommissionItemDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateCommissionItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control date-picker',
                    'readonly' => true,
                ],
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure début',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control time-input',
                ],
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure fin',
                'widget' => 'single_text',
                'html5' => false,
                'attr' => [
                    'class' => 'form-control time-input',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DateCommissionItemDto::class,
        ]);
    }
}
