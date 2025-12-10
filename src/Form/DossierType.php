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
    // @todo: Séparer en plusieurs FormTypes selon le type de dossier (Phase future)
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('type', EntityType::class, [
                'label' => 'Type de dossier',
                'class' => DossierTypeEntity::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un type',
                'required' => true,
                'disabled' => $isEdit, // Désactivé en édition
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_type',
                    'data-action' => 'change',
                ],
            ])
            ->add('nature', EntityType::class, [
                'label' => 'Nature du dossier',
                'class' => DossierNatureListe::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une nature',
                'required' => true,
                'disabled' => $isEdit, // Désactivé en édition
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_nature',
                    'data-action' => 'change',
                ],
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
            ->add('dateReception', DateType::class, [
                'label' => 'Date de réception SDIS',
                'widget' => 'single_text',
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

            // Champs spécifiques réunions (natures 31, 32, 43)
            ->add('demandeur', TextType::class, [
                'label' => 'Demandeur',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_demandeur',
                ],
            ])
            ->add('lieuReunion', TextType::class, [
                'label' => 'Lieu de réunion',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_lieuReunion',
                ],
            ])
            ->add('dateReunion', DateType::class, [
                'label' => 'Date de réunion',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                    'id' => 'dossier_dateReunion',
                ],
            ])
            ->add('dateReponse', DateType::class, [
                'label' => 'Date de réponse',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control datepicker',
                    'id' => 'dossier_dateReponse',
                ],
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'id' => 'dossier_observations',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'is_edit' => false, // Par défaut, mode création
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'dossier_form',
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
