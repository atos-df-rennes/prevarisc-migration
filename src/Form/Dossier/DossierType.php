<?php

namespace App\Form\Dossier;

use App\Entity\Avis;
use App\Entity\Commission;
use App\Entity\CommissionType;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\DossierPieceJointe;
use App\Entity\DossierType as DossierTypeEntity;
use App\Entity\PieceJointe;
use App\Entity\Utilisateur;
use App\Form\DataTransformer\DateCommissionTransformer;
use App\Repository\DossierNatureListeRepository;
use App\Repository\DossierPieceJointeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints as Assert;

class DossierType extends AbstractType
{
    /** @var Security */
    private $security;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var bool */
    private $facteurCriticiteCustomEnabled;

    public function __construct(Security $security, EntityManagerInterface $entityManager, bool $facteurCriticiteCustomEnabled)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
        $this->facteurCriticiteCustomEnabled = $facteurCriticiteCustomEnabled;
    }

    // Note : Possibilité future de séparer en plusieurs FormTypes selon le type de dossier
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $canEditAvis = $this->security->isGranted('edit_avis_com');

        $builder
            ->add('type', EntityType::class, [
                'label' => 'Type',
                'class' => DossierTypeEntity::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un type',
                'required' => true,
                'attr' => [
                    'data-action' => 'change',
                ],
            ])
            ->add('nature', EntityType::class, [
                'label' => 'Nature',
                'class' => DossierNatureListe::class,
                'choices' => $this->getAvailableNatures($options),
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une nature',
                'required' => true,
                'attr' => [
                    'data-action' => 'change',
                ],
            ])
            ->add('dateInsert', DateType::class, [
                'label' => 'Date de création du dossier',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('objet', TextareaType::class, [
                'label' => 'Objet',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            // Champs spécifiques études (natures 1, 2, etc.)
            ->add('documentsUrbanisme', CollectionType::class, [
                'entry_type' => DossierDocumentUrbanismeType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => "Documents d'urbanisme",
                'required' => false,
                'attr' => [
                    'class' => 'documents-urbanisme-collection',
                ],
            ])
            // Champs spécifiques dérogations (nature 3)
            ->add('justificationDerogation', TextareaType::class, [
                'label' => 'Justification de la dérogation',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('mesuresCompensatoires', TextareaType::class, [
                'label' => 'Mesures compensatoires',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('mesuresComplementaires', TextareaType::class, [
                'label' => 'Mesures complémentaires',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('reglesDerogation', TextareaType::class, [
                'label' => 'Règles de dérogation',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('dateSecretariat', DateType::class, [
                'label' => 'Date de réception secrétariat commission',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('serviceInstructeur', TextType::class, [
                'label' => 'Service instructeur',
                'required' => false,
                'attr' => [
                    'class' => 'tomselect-ajax',
                    'placeholder' => 'Sélectionnez ou saisissez un service instructeur',
                    'data-tomselect-config' => json_encode([
                        'create' => true,
                        'maxItems' => 1,
                        'plugins' => [],
                    ]),
                ],
                'constraints' => [
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('incomplet', ChoiceType::class, [
                'label' => 'État dossier',
                'choices' => [
                    'Vide' => '',
                    'Complet' => false,
                    'Incomplet' => true,
                ],
                'expanded' => true, // Radios au lieu de select
                'required' => false, // Pas required car champ masqué dynamiquement
                'empty_data' => '', // Valeur par défaut si radios désactivés (sera null en base)
            ])
            ->add('documentsManquants', CollectionType::class, [
                'entry_type' => DossierDocumentManquantType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'attr' => [
                    'class' => 'documents-manquants-collection',
                ],
            ])
            ->add('horsDelai', CheckboxType::class, [
                'label' => 'Hors délai',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input horsdelai hideavis',
                ],
            ])
            ->add('commission', EntityType::class, [
                'label' => 'Commission concernée',
                'class' => Commission::class,
                'choice_label' => 'libelle',
                'group_by' => function (Commission $commission): ?string {
                    $type = $commission->getType();

                    return $type instanceof CommissionType ? $type->getLibelle() : 'Autre';
                },
                'placeholder' => 'Aucune commission',
                'required' => false,
            ])
            ->add('dateReception', DateType::class, [
                'label' => 'Date de réception SDIS',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateVisite', DateType::class, [
                'label' => 'Date de visite',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'readonly' => true,
                    'class' => 'calendar-date-field',
                ],
            ])
            // @todo: vérifier si on a vraiment besoin de ça ou si on peut passer directement par la requête.
            ->add('idAffectationDossierVisite', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('dateLimitePrescription', DateType::class, [
                'label' => 'Date avant laquelle les prescriptions doivent être levées',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('avisCommission', EntityType::class, [
                'label' => 'Avis de la commission',
                'class' => Avis::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un avis',
                'required' => false,
                'disabled' => !$canEditAvis,
            ])
            ->add('repercuterAvis', CheckboxType::class, [
                'label' => "Répercuter l'avis sur les établissements enfants",
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'disabled' => !$canEditAvis,
            ])
            ->add('avis', EntityType::class, [
                'label' => false,
                'class' => Avis::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner un avis',
                'required' => false,
            ])
            ->add('dateCommission', DateType::class, [
                'label' => 'Date de commission en salle',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'readonly' => true,
                    'class' => 'calendar-date-field',
                ],
            ])
            // @todo: vérifier si on a vraiment besoin de ça ou si on peut passer directement par la requête.
            ->add('idAffectationDossierCommission', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('avisDiffere', CheckboxType::class, [
                'label' => 'Diffère l\'avis',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'help' => "(Disponible uniquement lorsque l'avis d'exploitation de l'établissement est favorable)",
            ])
            ->add('absenceDeQuorum', CheckboxType::class, [
                'label' => 'Absence de quorum',
                'required' => false,
                'attr' => ['class' => 'form-check-input absquorum hideavis'],
            ])
            ->add('facteurDangerosite', ChoiceType::class, [
                'label' => $this->facteurCriticiteCustomEnabled ? 'Signalement' : 'Facteur de criticité',
                'required' => false,
                'choices' => $this->facteurCriticiteCustomEnabled ? [
                    'Non' => 0,
                    'Oui' => 1,
                ] : [
                    1 => 1,
                    2 => 2,
                    3 => 3,
                    4 => 4,
                    5 => 5,
                ],
            ])
            ->add('echeancierTravaux', DateType::class, [
                'label' => 'Échéancier de travaux',
                'widget' => 'single_text',
                'required' => false,
            ])
            // Champs spécifiques réunions (natures 31, 32, 43)
            ->add('demandeur', TextType::class, [
                'label' => 'Demandeur',
                'required' => false,
                'attr' => [
                ],
            ])
            ->add('lieuReunion', TextType::class, [
                'label' => 'Lieu de réunion',
                'required' => false,
            ])
            ->add('dateReunion', DateType::class, [
                'label' => 'Date de réunion',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateReponse', DateType::class, [
                'label' => 'Date de réponse',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateSignature', DateType::class, [
                'label' => 'Date de signature',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('observations', TextareaType::class, [
                'label' => 'Observations',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('chargeSecurite', TextType::class, [
                'label' => 'Chargé de sécurité (T)',
                'required' => false,
            ])
            // Champs spécifiques interventions (natures 37, 38, 39)
            ->add('operationSdis', CheckboxType::class, [
                'label' => 'Opération (intervention) SDIS',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('rcci', CheckboxType::class, [
                'label' => 'RCCI (oui/non) lien rapport',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('rex', TextType::class, [
                'label' => 'REX',
                'required' => false,
            ])
            ->add('numeroIntervention', TextType::class, [
                'label' => "Numéro d'intervention",
                'required' => false,
            ])
            ->add('dateIntervention', DateTimeType::class, [
                'label' => "Date et heure d'intervention",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dureeIntervention', TimeType::class, [
                'label' => "Durée d'intervention",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('analyseDeRisque', TextareaType::class, [
                'label' => 'Analyse de risque',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                ],
            ])
            // Champs spécifiques courriers (natures 49, 51, 52, 53, 54, 55)
            ->add('dateMairie', DateType::class, [
                'label' => 'Date de réception mairie',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateEnvoiTransit', DateType::class, [
                'label' => "Date d'envoi / transit",
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateTransfertCommission', DateType::class, [
                'label' => 'Date de transfert à la commission compétente',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('dateReceptionCommission', DateType::class, [
                'label' => 'Date de réception à la commission compétente',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('datePrefecture', DateType::class, [
                'label' => 'Date réception préfecture',
                'widget' => 'single_text',
                'required' => false,
            ])
            // Champs spécifiques visites (natures 20-29)
            ->add('nePeutSePrononcer', CheckboxType::class, [
                'label' => 'Ne peut se prononcer',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input npsp hideavis',
                ],
            ])
            ->add('dateReceptionRvrat', DateType::class, [
                'label' => 'Date de réception du RVRAT et attestation solidité et MO',
                'widget' => 'single_text',
                'required' => false,
            ])
            // Préventionnistes (multiselect avec AJAX)
            // En création : tableau vide (aucune présélection, ajout uniquement via AJAX)
            // En édition : présélection des préventionnistes déjà affectés (actifs et inactifs)
            // La validation en soumission est gérée par le listener PRE_SUBMIT ci-dessous
            ->add('preventionnistes', EntityType::class, $this->buildPreventionnistesOptions(
                $options['is_edit'] && $options['data'] instanceof Dossier
                    ? $options['data']->getPreventionnistes()->toArray()
                    : []
            ))
        ;

        if ($options['is_edit']) {
            $builder
                // Pièces jointes exportables vers Plat'AU (multiselect avec checkboxes)
                ->add('piecesJointesPlatau', EntityType::class, [
                    'label' => 'Liste des pièces jointes pouvant être envoyées vers Plat\'AU',
                    'class' => PieceJointe::class,
                    'choices' => $this->getPiecesJointesExportables($options),
                    'choice_label' => function (PieceJointe $pieceJointe): string {
                        return $pieceJointe->getNom().$pieceJointe->getExtension();
                    },
                    'multiple' => true,
                    'expanded' => true,
                    // Affiche des checkboxes au lieu d'un select multiple
                    'required' => false,
                    'mapped' => false,
                    // Non mappé sur l'entité Dossier
                ])
                // Champs hidden pour gérer les demandes de renvoi Plat'AU
                // (UI uniquement, sauvegarde traitée dans un bloc ultérieur)
                ->add('platauRetryPec', HiddenType::class, [
                    'mapped' => false,
                    'required' => false,
                    'data' => '0',
                ])
                ->add('platauRetryAvis', HiddenType::class, [
                    'mapped' => false,
                    'required' => false,
                    'data' => '0',
                ])
            ;
        }

        // Ajout du transformer pour dateCommission (stockée en string dd/mm/yyyy)
        $builder->get('dateCommission')->addModelTransformer(new DateCommissionTransformer());

        // PRE_SUBMIT : Reconstruire les choix valides pour le champ preventionnistes
        // Permet de valider les IDs soumis via TomSelect AJAX sans charger tous les préventionnistes en HTML
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            if (!is_array($data)) {
                return;
            }

            // IDs soumis par TomSelect (dédoublonnés et castés en int)
            $submittedIds = isset($data['preventionnistes']) && is_array($data['preventionnistes'])
                ? array_values(array_unique(array_filter(array_map(
                    static function ($id): int { return is_scalar($id) ? (int) $id : 0; },
                    $data['preventionnistes']
                ))))
                : [];

            // IDs déjà affectés au dossier (pour conserver les inactifs)
            $dossier = $form->getData();
            $existingIds = $dossier instanceof Dossier
                ? array_values(array_unique($dossier->getPreventionnistes()->map(
                    static function (Utilisateur $u): int { return (int) $u->getId(); }
                )->toArray()))
                : [];

            /** @var UtilisateurRepository $repo */
            $repo = $this->entityManager->getRepository(Utilisateur::class);
            $validChoices = $repo->findValidPreventionnistesForSubmit($submittedIds, $existingIds);

            $form->add('preventionnistes', EntityType::class, $this->buildPreventionnistesOptions($validChoices));
        });

        // PRE_SUBMIT : Recalculer les natures disponibles en fonction du type sélectionné
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            // Vérification que data est un array
            if (!is_array($data)) {
                return;
            }

            // Si un type est soumis, recalculer les natures disponibles
            if (!isset($data['type']) || empty($data['type'])) {
                return;
            }

            // Cast sécurisé : vérifier que c'est un scalar avant de caster en int
            if (!is_scalar($data['type'])) {
                return;
            }

            $typeId = (int) $data['type'];
            $type = $this->entityManager->getRepository(DossierTypeEntity::class)->find($typeId);

            if (null === $type) {
                return;
            }

            /** @var DossierNatureListeRepository $repository */
            $repository = $this->entityManager->getRepository(DossierNatureListe::class);
            $availableNatures = $repository->findByTypeOrderedByOrdre($type);

            // Remplacer le champ nature avec les nouvelles options
            $form->add('nature', EntityType::class, [
                'label' => 'Nature',
                'class' => DossierNatureListe::class,
                'choices' => $availableNatures,
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionner une nature',
                'required' => true,
                'attr' => [
                    'data-action' => 'change',
                ],
            ]);
        });
    }

    /**
     * Récupère la liste des pièces jointes exportables vers Plat'AU pour un dossier.
     *
     * @param array<string, mixed> $options Options du formulaire
     *
     * @return PieceJointe[]
     */
    private function getPiecesJointesExportables(array $options): array
    {
        // Uniquement en édition et si le dossier existe
        if (
            !$options['is_edit']
            || !$options['data'] instanceof Dossier
            || null === $options['data']->getId()
        ) {
            return [];
        }

        /** @var DossierPieceJointeRepository $repository */
        $repository = $this->entityManager->getRepository(DossierPieceJointe::class);

        // Récupère les pièces jointes exportables (statut 'not_exported')
        return $repository->findPiecesJointesByStatut(
            $options['data']->getId(),
            'not_exported'
        );
    }

    /**
     * @param array<string, mixed> $options Options du formulaire
     *
     * @return DossierNatureListe[]
     */
    private function getAvailableNatures(array $options): array
    {
        // Uniquement en édition et si le dossier existe
        if (
            !$options['is_edit']
            || !$options['data'] instanceof Dossier
            || null === $options['data']->getId()
        ) {
            return [];
        }

        /** @var DossierNatureListeRepository $repository */
        $repository = $this->entityManager->getRepository(DossierNatureListe::class);

        $typeDossier = $options['data']->getType();

        if (null === $typeDossier) {
            return [];
        }

        return $repository->findByTypeOrderedByOrdre($typeDossier);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'is_edit' => false, // Par défaut, mode création
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'dossier_form',
            'validation_groups' => ['Default', 'edit_informations'],
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }

    /**
     * Retourne les options communes du champ EntityType pour les préventionnistes.
     *
     * Factorisé pour éviter la duplication entre le rendu initial (GET)
     * et la reconstruction en PRE_SUBMIT (POST).
     *
     * @param Utilisateur[] $choices Liste des utilisateurs à proposer comme options
     *
     * @return array<string, mixed>
     */
    private function buildPreventionnistesOptions(array $choices): array
    {
        return [
            'label' => 'Préventionniste(s) du dossier',
            'class' => Utilisateur::class,
            'choices' => $choices,
            'choice_label' => 'displayLabel',
            'multiple' => true,
            'required' => false,
            'attr' => ['class' => 'tomselect-ajax'],
        ];
    }
}
