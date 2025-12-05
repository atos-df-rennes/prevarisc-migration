<?php

namespace App\Form;

use App\Entity\ListeDocumentConsulte;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListeDocumentConsulteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextareaType::class)
            ->add('visible', ChoiceType::class, [
                'choices' => [
                    'Afficher' => true,
                    'Masquer' => false,
                ]
            ])
            ->add('visite', CheckboxType::class, [
                'label'    => 'Visite',
                'required' => false,
            ])
            ->add('etude', CheckboxType::class, [
                'label'    => 'Étude',
                'required' => false,
            ])
            ->add('receptionTravaux', CheckboxType::class, [
                'label'    => 'Réception des travaux',
                'required' => false,
            ])
            ->add('avantOuverture', CheckboxType::class, [
                'label'    => 'Avant ouverture',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Sauvegarder',
                'attr'  => ['class' => 'btn btn-success'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListeDocumentConsulte::class,
        ]);
    }
}
