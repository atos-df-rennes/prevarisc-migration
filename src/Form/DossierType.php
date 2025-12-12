<?php

namespace App\Form;

use App\Entity\Avis;
use App\Entity\Commission;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType as DossierTypeEntity;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\QueryBuilder;
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
                'label' => 'Type',
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
                'label' => 'Nature',
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
                'label' => 'Date de création du dossier',
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
            ->add('descriptifGeneral', TextareaType::class, [
                'label' => 'Descriptif général',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                ],
            ])
            ->add('descriptifEffectifs', TextareaType::class, [
                'label' => 'Descriptif effectifs',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'class' => 'form-control',
                ],
            ])
            ->add('coordonneesSsi', TextareaType::class, [
                'label' => 'Coordonnées SSI',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                ],
            ])
            ->add('commission', EntityType::class, [
                'label' => 'Commission concernée',
                'class' => Commission::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une commission',
                'required' => false,
                'attr' => ['class' => 'form-control'],
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
            ->add('dateLimitePrescription', DateType::class, [
                'label' => 'Date avant laquelle les prescriptions doivent être levées',
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
            ->add('avis', EntityType::class, [
                'label' => 'Avis du rapporteur',
                'class' => Avis::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un avis',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateCommission', DateType::class, [
                'label' => 'Date de commission en salle',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('avisDiffere', CheckboxType::class, [
                'label' => 'Diffère l\'avis',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ])
            ->add('absenceDeQuorum', CheckboxType::class, [
                'label' => 'Absence de quorum',
                'required' => false,
                'attr' => ['class' => 'form-check-input hideavis'],
            ])
            ->add('facteurDangerosite', IntegerType::class, [
                'label' => 'Facteur de dangerosité',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('echeancierTravaux', DateType::class, [
                'label' => 'Échéancier de travaux',
                'widget' => 'single_text',
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
                    'class' => 'form-control',
                    'id' => 'dossier_dateReunion',
                ],
            ])
            ->add('dateReponse', DateType::class, [
                'label' => 'Date de réponse',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
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
            ->add('analyseDeRisque', TextareaType::class, [
                'label' => 'Analyse de risque',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'id' => 'dossier_analyseDeRisque',
                ],
            ])

            // Champs spécifiques courriers (natures 49, 51, 52, 53, 54, 55)
            ->add('dateMairie', DateType::class, [
                'label' => 'Date de réception mairie',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_dateMairie',
                ],
            ])
            ->add('dateEnvoiTransit', DateType::class, [
                'label' => "Date d'envoi / transit",
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_dateEnvoiTransit',
                ],
            ])
            ->add('dateTransfertCommission', DateType::class, [
                'label' => 'Date de transfert à la commission compétente',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_dateTransfertCommission',
                ],
            ])
            ->add('dateReceptionCommission', DateType::class, [
                'label' => 'Date de réception à la commission compétente',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'id' => 'dossier_dateReceptionCommission',
                ],
            ])

            // Préventionnistes (multiselect)
            ->add('preventionnistes', EntityType::class, [
                'label' => 'Préventionniste(s) du dossier',
                'class' => Utilisateur::class,
                'query_builder' => function (UtilisateurRepository $repository): QueryBuilder {
                    return $repository->createPreventionnistesQueryBuilder();
                },
                'choice_label' => function (Utilisateur $user): string {
                    $info = $user->getInformations();

                    if (null !== $info) {
                        $fullName = $info->getFullName();

                        if ('' !== $fullName) {
                            return $fullName;
                        }
                    }

                    return $user->getUsername();
                },
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'tomselect-ajax'],
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
