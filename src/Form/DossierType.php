<?php

namespace App\Form;

use App\Entity\Avis;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType as DossierTypeEntity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DossierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EntityType::class, [
                'label' => 'Type de dossier',
                'class' => DossierTypeEntity::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un type',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('nature', EntityType::class, [
                'label' => 'Nature du dossier',
                'class' => DossierNatureListe::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une nature',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateInsert', DateType::class, [
                'label' => "Date d'insertion",
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('objet', TextareaType::class, [
                'label' => 'Objet',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
            ->add('demandeur', TextType::class, [
                'label' => 'Demandeur',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateVisite', DateType::class, [
                'label' => 'Date de visite',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('avisCommission', EntityType::class, [
                'label' => 'Avis de la commission',
                'class' => Avis::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un avis',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('avisDiffere', CheckboxType::class, [
                'label' => 'Avis différé',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('facteurDangerosite', IntegerType::class, [
                'label' => 'Facteur de dangerosité',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
        ]);
    }
}
