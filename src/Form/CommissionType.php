<?php

namespace App\Form;

use App\Entity\Commission;
use App\Entity\CommissionType as CommissionTypeEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => false,
                'required' => true,
                'row_attr' => [
                    'class' => 'col-sm-5'
                ],
                'attr' => [
                    'placeholder' => 'Libellé de la commission',
                ],
            ])
            ->add('type', EntityType::class, [
                'label' => false,
                'class' => CommissionTypeEntity::class,
                'choice_label' => 'libelle',
                'required' => true,
                'row_attr' => [
                    'class' => 'col-sm-5'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commission::class,
        ]);
    }
}
