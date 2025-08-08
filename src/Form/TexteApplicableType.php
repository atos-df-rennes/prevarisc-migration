<?php

namespace App\Form;

use App\Entity\TexteApplicable;
use App\Entity\TypeTexteApplicable;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TexteApplicableType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextareaType::class, [
                'label' => false,
            ])
            ->add('visible', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Afficher' => true,
                    'Masquer' => false,
                ]
            ])
            ->add('num', HiddenType::class, [
                'empty_data' => 9999,
            ])
            ->add('type', EntityType::class, [
                'label' => false,
                'class' => TypeTexteApplicable::class,
                'choice_label' => 'libelle',
            ])
            ->add('prescription', ChoiceType::class, [
                'label' => 'Rendre ce texte disponible pour les prescriptions',
                'mapped' => false,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'data' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sauvegarder',
                'attr' => ['class' => 'btn btn-success'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TexteApplicable::class,
        ]);
    }
}
